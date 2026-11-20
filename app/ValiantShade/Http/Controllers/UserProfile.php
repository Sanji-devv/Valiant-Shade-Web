<?php

namespace App\ValiantShade\Http\Controllers;

use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfile extends Controller
{
  public function index()
  {
    $user = Auth::user();
    abort_unless($user, 403);
    $loadEnum = function ($file) {
      $candidates = [
        config_path('enums/' . $file),
        config_path('Enums/' . $file),
        config_path('enums/' . str_replace('pathfinder_', '', $file)),
        config_path('Enums/' . str_replace('pathfinder_', '', $file)),
      ];
      foreach ($candidates as $p) {
        if (file_exists($p)) return require $p;
      }
      return [];
    };

    $pfRaces = $loadEnum('races.php');
    $pfClasses = $loadEnum('classes.php');
    $bab = $loadEnum('bab.php');
    $progressions = $pfClasses;
    $saves = array_map(fn($c) => $c['saves'] ?? [], $progressions);

    $vault = \App\Models\GuildVault::firstOrCreate(
      ['name' => 'Ana Lonca Kasası'],
      ['balance_sp' => 358989.50, 'notes' => 'Valiant Shade lonca ana kasası']
    );

    $allCharacters = \App\Models\CharacterProfile::with('user')
      ->orderByRaw("CASE WHEN rank = 'Main' OR character_type = 'Main' THEN 1 WHEN rank = 'Clerk' OR character_type = 'Clerk' THEN 2 ELSE 3 END")
      ->orderBy('character_name', 'asc')
      ->get();

    $userCharacters = $user
      ? \App\Models\CharacterProfile::where('user_id', $user->id)
          ->orderByRaw("CASE WHEN rank = 'Main' OR character_type = 'Main' THEN 1 WHEN rank = 'Clerk' OR character_type = 'Clerk' THEN 2 ELSE 3 END")
          ->orderBy('id', 'asc')
          ->get()
      : collect();

    // If current user has own characters use them; otherwise fallback to first 2 from guild characters
    $characters = ($user && $userCharacters->isNotEmpty()) ? $userCharacters : ($user ? collect() : $allCharacters->take(2));

    $mapCharacter = function ($c) use ($user) {
      $sData = $c->sheet_data['sheetData'] ?? $c->sheet_data ?? [];
      $traits = $sData['traits'] ?? ($c->sheet_data['traits'] ?? [
        'uye' => ['name' => '', 'desc' => ''],
        'kidemli' => ['name' => '', 'desc' => ''],
        'uzman' => ['name' => '', 'desc' => ''],
      ]);
      $gold = $sData['moneyGp'] ?? ($c->sheet_data['money']['gp'] ?? 150);

      return [
        'id' => $c->character_id ?: (string) $c->id,
        'db_id' => $c->id,
        'name' => $c->character_name,
        'player' => $c->player_name ?: ($c->user?->name ?: ($user?->name ?? 'Player')),
        'race' => $c->race ?: 'Human',
        'raceKey' => strtolower($c->race ?? 'human'),
        'class' => $c->class_name ?: 'Fighter',
        'classKey' => strtolower($c->class_name ?? 'fighter'),
        'rank' => $c->rank ?: 'Main',
        'character_type' => $c->character_type ?: ($c->rank ?: 'Main'),
        'level' => (int) ($c->level ?: 1),
        'hp' => (int) ($c->hp ?: 10),
        'maxHp' => (int) ($c->max_hp ?: ($c->hp ?: 10)),
        'hitDie' => $c->hit_die ?: 'd10',
        'str' => (int) ($c->str ?: 10),
        'dex' => (int) ($c->dex ?: 10),
        'con' => (int) ($c->con ?: 10),
        'int' => (int) ($c->int ?: 10),
        'wis' => (int) ($c->wis ?: 10),
        'cha' => (int) ($c->cha ?: 10),
        'gold' => $gold,
        'active' => ($user && $user->current_character === $c->character_name),
        'sheetData' => $sData,
        'traits' => $traits,
      ];
    };

    $charactersData = $characters->map($mapCharacter)->values();
    $allCharactersData = $allCharacters->map($mapCharacter)->values();

    // Resolve all user IDs belonging to this player/account
    $userIds = $user ? [$user->id] : [];
    if ($user) {
      if (!empty($user->current_character)) {
        $owner = \App\Models\Character::where('name', $user->current_character)->value('user_id');
        if ($owner && !in_array($owner, $userIds)) $userIds[] = $owner;
      }
      $profChars = \App\Models\CharacterProfile::where('user_id', $user->id)->pluck('character_name')->toArray();
      if (!empty($profChars)) {
        $owners = \App\Models\Character::whereIn('name', $profChars)->pluck('user_id')->toArray();
        foreach ($owners as $o) {
          if ($o && !in_array($o, $userIds)) $userIds[] = $o;
        }
      }
      if ($user->id == 1 || strcasecmp($user->email, 'admin@valiantshade.com') === 0 || stripos($user->name, 'erdem') !== false) {
        $likeOp = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $matched = \App\Models\User::where('name', $likeOp, '%erdem%')->pluck('id')->toArray();
        foreach ($matched as $m) {
          if (!in_array($m, $userIds)) $userIds[] = $m;
        }
      }
    }

    // Query user's dead (KIA), retired (INACTIVE), lost (MIA) characters
    $pastCharacters = \App\Models\Character::with('rank')
      ->whereIn('user_id', $userIds)
      ->whereIn('status', ['KIA', 'INACTIVE', 'MIA', 'TRAVELER'])
      ->orderByRaw("CASE WHEN status = 'KIA' THEN 1 WHEN status = 'INACTIVE' THEN 2 WHEN status = 'MIA' THEN 3 ELSE 4 END")
      ->orderBy('current_level', 'desc')
      ->orderBy('total_xp', 'desc')
      ->get();

    // If the user has no past characters, it remains an empty collection to display the empty state.
    $pastCharacterNames = $pastCharacters->pluck('name')->toArray();
    $profilesByName = \App\Models\CharacterProfile::whereIn('character_name', $pastCharacterNames)->get()->keyBy('character_name');

    $questCounts = \App\Models\QuestParticipant::whereIn('character_id', $pastCharacters->pluck('id'))
      ->groupBy('character_id')
      ->selectRaw('character_id, count(*) as count')
      ->pluck('count', 'character_id');

    $pastCharactersData = $pastCharacters->map(function ($c) use ($profilesByName, $questCounts, $user) {
      $prof = $profilesByName->get($c->name);
      return [
        'id' => $c->id,
        'name' => $c->name,
        'status' => strtoupper($c->status ?: 'KIA'),
        'character_type' => $c->character_type ?: 'MAIN',
        'level' => (int) ($c->current_level ?: 1),
        'rank' => $c->rank?->name ?? 'Aday',
        'total_xp' => (float) ($c->total_xp ?: 0),
        'quest_xp' => (float) ($c->quest_xp ?: 0),
        'report_xp' => (float) ($c->report_xp ?: 0),
        'quests_count' => (int) ($questCounts[$c->id] ?? 0),
        'class' => $prof?->class_name ?: 'Maceracı',
        'race' => $prof?->race ?: 'Human',
        'player' => $user?->name ?? 'Player',
        'created_at' => $c->created_at ? $c->created_at->format('d.m.Y') : '',
      ];
    })->values();

    return view('valiant-shade.content.pages.pages-profile-user', compact(
      'user',
      'characters',
      'charactersData',
      'allCharacters',
      'allCharactersData',
      'pastCharacters',
      'pastCharactersData',
      'profilesByName',
      'questCounts',
      'pfRaces',
      'pfClasses',
      'bab',
      'saves',
      'progressions',
      'vault'
    ));
  }

  public function vaultTransfer(Request $request)
  {
    $validated = $request->validate([
      'operation' => ['required', 'string', 'in:deposit,withdraw'],
      'amount_sp' => ['required', 'numeric', 'min:0.01'],
      'character_name' => ['required', 'string', 'max:100'],
      'notes' => ['nullable', 'string', 'max:255'],
    ]);

    $vault = \App\Models\GuildVault::firstOrCreate(
      ['name' => 'Ana Lonca Kasası'],
      ['balance_sp' => 358989.50, 'notes' => 'Valiant Shade lonca ana kasası']
    );

    $amountSp = (float) $validated['amount_sp'];
    $charName = $validated['character_name'];
    $notes = $validated['notes'] ?: ($validated['operation'] === 'deposit'
      ? "{$charName} tarafından kasaya yatırıldı"
      : "{$charName} tarafından kasadan çekildi");

    if ($validated['operation'] === 'deposit') {
      $vault->balance_sp = ((float) $vault->balance_sp) + $amountSp;
    } else {
      if (((float) $vault->balance_sp) < $amountSp) {
        return response()->json([
          'success' => false,
          'message' => __('Insufficient funds in the guild vault.')
        ], 422);
      }
      $vault->balance_sp = max(0, ((float) $vault->balance_sp) - $amountSp);
    }

    $vault->notes = $notes;
    $vault->save();

    return response()->json([
      'success' => true,
      'message' => __('Vault balance updated successfully.'),
      'vault' => [
        'name' => $vault->name,
        'balance_sp' => (float) $vault->balance_sp,
        'balance_gp' => round(((float) $vault->balance_sp) / 10, 2),
        'notes' => $vault->notes,
      ]
    ]);
  }

  public function createCharacter(Request $request)
  {
    $user = Auth::user();
    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => __('Please sign in to create a character.'),
      ], 401);
    }

    return $this->createCharacterForUser($request, $user);
  }

  public function createCharacterForUser(Request $request, \App\Models\User $user)
  {
    $actor = Auth::user();
    $isAdmin = $actor && ($actor->isAdmin() || strcasecmp($actor->email, 'admin@valiantshade.com') === 0 || $actor->hasRole('admin'));

    if (!$isAdmin) {
      $existingCount = \App\Models\CharacterProfile::where('user_id', $user->id)->count();
      if ($existingCount >= 2) {
        return response()->json([
          'success' => false,
          'message' => __('Character limit reached. Each player can have at most 2 characters (1 Main and 1 Clerk).'),
        ], 422);
      }
    }

    $validated = $request->validate([
      'character_name' => ['required', 'string', 'min:2', 'max:50'],
      'rank' => ['required', 'string', 'in:Main,Clerk'],
      'race' => ['required', 'string', 'max:50'],
      'class_name' => ['required', 'string', 'max:50'],
      'str' => ['required', 'integer', 'min:1', 'max:30'],
      'dex' => ['required', 'integer', 'min:1', 'max:30'],
      'con' => ['required', 'integer', 'min:1', 'max:30'],
      'int' => ['required', 'integer', 'min:1', 'max:30'],
      'wis' => ['required', 'integer', 'min:1', 'max:30'],
      'cha' => ['required', 'integer', 'min:1', 'max:30'],
    ]);

    // Check unique character name across profiles and characters
    $nameExists = \App\Models\CharacterProfile::whereRaw('LOWER(character_name) = ?', [mb_strtolower($validated['character_name'])])->exists()
      || \App\Models\Character::whereRaw('LOWER(name) = ?', [mb_strtolower($validated['character_name'])])->exists();

    if ($nameExists) {
      return response()->json([
        'success' => false,
        'message' => __("The character name ':name' is already taken. Please choose another name.", ['name' => $validated['character_name']]),
      ], 422);
    }

    $isClerk = (strcasecmp($validated['rank'], 'Clerk') === 0);
    $charType = $isClerk ? 'Clerk' : 'Main';
    $guildRank = $isClerk ? 'Clerk' : 'Aday'; // Level 1 adventurer rank is Aday

    // Check if user already has an active character of this type
    if (!$isAdmin) {
      $slotTaken = \App\Models\CharacterProfile::where('user_id', $user->id)
        ->where(function ($q) use ($isClerk) {
          if ($isClerk) {
            $q->whereIn('character_type', ['CLERK', 'Clerk'])->orWhere('rank', 'Clerk');
          } else {
            $q->whereIn('character_type', ['MAIN', 'Main'])->orWhereIn('rank', ['Main', 'Aday', 'Üye', 'Kıdemli', 'Uzman']);
          }
        })
        ->exists();

      if ($slotTaken) {
        return response()->json([
          'success' => false,
          'message' => __("You already have a :slot character. You can only create one Main and one Clerk.", ['slot' => $validated['rank']]),
        ], 422);
      }
    }

    $loadEnum = function ($file) {
      $candidates = [
        config_path('enums/' . $file),
        config_path('Enums/' . $file),
        config_path('enums/' . str_replace('pathfinder_', '', $file)),
        config_path('Enums/' . str_replace('pathfinder_', '', $file)),
      ];
      foreach ($candidates as $p) {
        if (file_exists($p)) return require $p;
      }
      return [];
    };

    // Robust class lookup
    $pfClasses = $loadEnum('classes.php');
    $clsInfo = [];
    $classKey = strtolower($validated['class_name']);
    foreach ($pfClasses as $k => $c) {
      if (strcasecmp($k, $validated['class_name']) === 0 || strcasecmp($c['name'] ?? '', $validated['class_name']) === 0) {
        $clsInfo = $c;
        $classKey = $k;
        break;
      }
    }
    $classDisplayName = $clsInfo['name'] ?? ucfirst($validated['class_name']);

    // Robust race lookup
    $pfRaces = $loadEnum('races.php');
    $raceInfo = [];
    $raceKey = strtolower($validated['race']);
    foreach ($pfRaces as $k => $r) {
      if (strcasecmp($k, $validated['race']) === 0 || strcasecmp($r['name'] ?? '', $validated['race']) === 0) {
        $raceInfo = $r;
        $raceKey = $k;
        break;
      }
    }
    $raceDisplayName = $raceInfo['name'] ?? ucfirst($validated['race']);

    // Hit Die and starting HP
    $hitDie = $clsInfo['hit_die'] ?? 'd10';
    $hdNumber = (int) filter_var($hitDie, FILTER_SANITIZE_NUMBER_INT) ?: 10;
    $conMod = (int) floor(($validated['con'] - 10) / 2);
    $startingHp = max(1, $hdNumber + $conMod);

    // BAB and Saves at level 1
    $babConfig = $loadEnum('bab.php');
    $babTableKey = $clsInfo['bab_table'] ?? 'bab_section_1';
    $bab = $babConfig[$babTableKey][1] ?? '+1';
    $classSaves = $clsInfo['saves'][1] ?? ['fort' => '+2', 'ref' => '+0', 'will' => '+0'];
    $castingStat = $clsInfo['casting_stat'] ?? 'none';

    $initialSpecials = [];
    if (!empty($clsInfo['specials'][1])) {
      $specStr = $clsInfo['specials'][1];
      if ($specStr !== '—' && $specStr !== '-') {
        $parts = array_filter(array_map('trim', explode(',', $specStr)));
        foreach ($parts as $part) {
          $initialSpecials[] = [
            'badge' => 'Lvl 1',
            'title' => ucfirst($part),
            'desc' => "{$classDisplayName} Lvl 1 Class Feature",
          ];
        }
      }
    }

    $charId = 'char_' . round(microtime(true) * 1000);

    $initialSheetData = [
      'charId' => $charId,
      'name' => $validated['character_name'],
      'player' => $user->name,
      'alignment' => 'Lawful Good (LG)',
      'level' => 1,
      'deity' => '',
      'classKey' => $classKey,
      'className' => $classDisplayName,
      'race' => $raceDisplayName,
      'rank' => $guildRank,
      'character_type' => $charType,
      'size' => $raceInfo['size'] ?? 'Medium',
      'gender' => 'Male',
      'age' => 25,
      'height' => "6'0\"",
      'weight' => '180 lbs',
      'eyes' => 'Blue',
      'hair' => 'Blonde',
      'skin' => 'Fair',
      'str' => $validated['str'],
      'dex' => $validated['dex'],
      'con' => $validated['con'],
      'int' => $validated['int'],
      'wis' => $validated['wis'],
      'cha' => $validated['cha'],
      'abilityScores' => [
        'str' => $validated['str'],
        'dex' => $validated['dex'],
        'con' => $validated['con'],
        'int' => $validated['int'],
        'wis' => $validated['wis'],
        'cha' => $validated['cha'],
      ],
      'currentHp' => $startingHp,
      'maxHp' => $startingHp,
      'hpExtraMisc' => 0,
      'tempHp' => 0,
      'nonLethal' => 0,
      'hitDie' => $hitDie,
      'speedBase' => (int) filter_var($raceInfo['speed'] ?? '30 ft.', FILTER_SANITIZE_NUMBER_INT) ?: 30,
      'speedArmor' => 30,
      'initMisc' => 0,
      'acArmor' => 0,
      'acShield' => 0,
      'acDex' => (int) floor(($validated['dex'] - 10) / 2),
      'acSize' => 0,
      'acNat' => 0,
      'acDef' => 0,
      'acMisc' => 0,
      'ffMisc' => 0,
      'touchMisc' => 0,
      'dr' => '',
      'sr' => '',
      'fortBase' => (int) filter_var($classSaves['fort'] ?? '+2', FILTER_SANITIZE_NUMBER_INT),
      'fortMagic' => 0,
      'fortMisc' => 0,
      'fortTemp' => 0,
      'refBase' => (int) filter_var($classSaves['ref'] ?? '+0', FILTER_SANITIZE_NUMBER_INT),
      'refMagic' => 0,
      'refMisc' => 0,
      'refTemp' => 0,
      'willBase' => (int) filter_var($classSaves['will'] ?? '+0', FILTER_SANITIZE_NUMBER_INT),
      'willMagic' => 0,
      'willMisc' => 0,
      'willTemp' => 0,
      'bab' => $bab,
      'cmbSize' => 0,
      'cmbMisc' => 0,
      'cmdSize' => 0,
      'cmdMisc' => 0,
      'skills' => [],
      'weapons' => ($classKey === 'alchemist') ? [
        [
          'name' => 'Bomb',
          'atkType' => 'ranged',
          'type' => 'Fire',
          'range' => '20 ft.',
          'baseDie' => '1d6',
          'enh' => 0,
          'miscAtk' => 0,
          'critRange' => '20',
          'critMult' => 2,
          'ammo' => max(1, 1 + (int) floor(($validated['int'] - 10) / 2)) . '/day',
          'atkBonus' => sprintf('%+d', (int) filter_var($bab, FILTER_SANITIZE_NUMBER_INT) + (int) floor(($validated['dex'] - 10) / 2)),
          'dmg' => '1d6' . (((int) floor(($validated['int'] - 10) / 2)) !== 0 ? sprintf('%+d', (int) floor(($validated['int'] - 10) / 2)) : ''),
          'critDmg' => '2d6' . (((int) floor(($validated['int'] - 10) / 2)) !== 0 ? sprintf('%+d', (int) floor(($validated['int'] - 10) / 2) * 2) : ''),
        ]
      ] : [],
      'acItems' => [],
      'gear' => [],
      'feats' => [],
      'traits' => [
        'uye' => ['name' => '', 'desc' => ''],
        'kidemli' => ['name' => '', 'desc' => ''],
        'uzman' => ['name' => '', 'desc' => ''],
      ],
      'specials' => $initialSpecials,
      'castingStat' => $castingStat,
      'spells' => ['castingStat' => $castingStat, 'slots' => [], 'list' => []],
      'money' => ['cp' => 0, 'sp' => 0, 'gp' => 150, 'pp' => 0],
      'currentXp' => 0,
      'nextLevelXp' => 1300,
    ];

    $profile = \App\Models\CharacterProfile::create([
      'character_id' => $charId,
      'user_id' => $user->id,
      'character_name' => $validated['character_name'],
      'player_name' => $user->name,
      'race' => $raceDisplayName,
      'class_name' => $classDisplayName,
      'level' => 1,
      'rank' => ucfirst(strtolower($validated['rank'])),
      'character_type' => $charType,
      'str' => $validated['str'],
      'dex' => $validated['dex'],
      'con' => $validated['con'],
      'int' => $validated['int'],
      'wis' => $validated['wis'],
      'cha' => $validated['cha'],
      'hp' => $startingHp,
      'max_hp' => $startingHp,
      'hit_die' => $hitDie,
      'bab' => $bab,
      'fort_save' => $classSaves['fort'] ?? '+2',
      'ref_save' => $classSaves['ref'] ?? '+0',
      'will_save' => $classSaves['will'] ?? '+0',
      'sheet_data' => $initialSheetData,
    ]);

    // Also persist into core Character model
    $rankModel = \App\Models\Rank::where('name', $guildRank)->first();
    if (!$rankModel && \Illuminate\Support\Facades\Schema::hasTable('ranks')) {
      $rankModel = \App\Models\Rank::firstOrCreate(
        ['name' => $guildRank],
        ['hierarchy_order' => $isClerk ? 2 : 1, 'base_floor_xp' => 0.00]
      );
    }
    $char = \App\Models\Character::firstOrCreate(
      ['name' => $validated['character_name']],
      [
        'user_id' => $user->id,
        'character_type' => strtoupper($charType),
        'status' => 'ACTIVE',
        'rank_id' => $rankModel?->id,
        'current_level' => 1,
        'allowance_sp' => 0.00,
        'total_xp' => 0.00,
      ]
    );

    // Ensure CharacterInfo exists
    \App\Models\CharacterInfo::firstOrCreate(
      ['character_id' => $char->id],
      [
        'class_and_level' => "{$classDisplayName} 1",
        'race' => $raceDisplayName,
        'alignment' => 'TN',
        'hp_max' => $startingHp,
        'hp_current' => $startingHp,
        'armor_class_total' => 10 + (int) floor(($validated['dex'] - 10) / 2),
        'touch_ac' => 10 + (int) floor(($validated['dex'] - 10) / 2),
        'flat_footed_ac' => 10,
        'pocket_money' => ['pp' => 0, 'gp' => 150, 'sp' => 0, 'cp' => 0],
      ]
    );

    // Sync with PlayerXp model
    \App\Models\PlayerXp::firstOrCreate(
      ['character_name' => $validated['character_name']],
      [
        'user_id' => $user->id,
        'player_name' => $user->name,
        'status' => 'active',
        'rank' => $guildRank,
        'level' => 1,
        'sessions_count' => 0,
        'total_xp' => 0,
        'allowance_sp' => 0,
      ]
    );

    // Assign active character on user if not set
    if ($charType === 'MAIN' && !$user->active_main_character_id) {
      $user->update(['active_main_character_id' => $char->id]);
    } elseif ($charType === 'CLERK' && !$user->active_clerk_character_id) {
      $user->update(['active_clerk_character_id' => $char->id]);
    }

    if (empty($user->current_character)) {
      $user->current_character = $profile->character_name;
      $user->save();
    }

    return response()->json([
      'success' => true,
      'message' => __(':name (:rank) created successfully!', ['name' => $profile->character_name, 'rank' => $profile->rank]),
      'character' => $profile,
    ]);
  }

  public function deleteCharacter(Request $request, $id)
  {
    $user = Auth::user();
    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => __('Unauthorized.'),
      ], 401);
    }

    $profile = \App\Models\CharacterProfile::where('user_id', $user->id)
      ->where(function ($q) use ($id) {
        $q->where('character_id', (string) $id);
        if (is_numeric($id)) {
          $q->orWhere('id', (int) $id);
        }
      })
      ->first();

    if (!$profile) {
      return response()->json([
        'success' => false,
        'message' => __('Character not found or you do not have permission to delete it.'),
      ], 404);
    }

    $name = $profile->character_name;
    $profile->delete();

    if ($user->current_character === $name) {
      $nextChar = \App\Models\CharacterProfile::where('user_id', $user->id)->first();
      $user->current_character = $nextChar ? $nextChar->character_name : null;
      $user->save();
    }

    return response()->json([
      'success' => true,
      'message' => __('Character :name deleted successfully.', ['name' => $name]),
    ]);
  }

  public function setActiveCharacter(Request $request)
  {
    $user = Auth::user();
    if (!$user) {
      return response()->json(['success' => false, 'message' => __('Unauthorized.')], 401);
    }

    $name = $request->input('character');
    $profile = \App\Models\CharacterProfile::where('user_id', $user->id)
      ->where('character_name', $name)
      ->first();

    if ($profile) {
      $user->current_character = $profile->character_name;
      $user->save();
      return response()->json(['success' => true, 'character' => $profile->character_name]);
    }

    return response()->json(['success' => false, 'message' => __('Character not found.')], 404);
  }

  /**
   * Get list of claimable characters from the XP registry (characters table).
   */
  public function getClaimableCharacters(Request $request)
  {
    $search = $request->query('q', '');
    $query = \App\Models\Character::with('rank')
      ->whereNotNull('name')
      ->where('name', '!=', '')
      ->orderByRaw('LOWER(TRIM(name)) ASC');

    if (!empty($search)) {
      $term = mb_strtolower(trim($search));
      $query->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]);
    }

    $chars = $query->get()->map(function ($c) {
      return [
        'id' => $c->id,
        'name' => $c->name,
        'level' => (int) $c->current_level,
        'total_xp' => (float) $c->total_xp,
        'character_type' => $c->character_type ?: 'MAIN',
        'rank_name' => $c->rank?->name ?? 'Üye',
        'allowance_sp' => (float) ($c->allowance_sp ?? 0),
      ];
    });

    return response()->json([
      'success' => true,
      'characters' => $chars,
    ]);
  }

  /**
   * Get detailed progression, feats, stats, and requirements for a claimable character.
   */
  public function getClaimableCharacterDetails($idOrName)
  {
    $character = \App\Models\Character::with('rank')
      ->where('id', is_numeric($idOrName) ? (int)$idOrName : 0)
      ->orWhere('name', $idOrName)
      ->first();

    if (!$character) {
      return response()->json([
        'success' => false,
        'message' => __('Character not found in XP registry.'),
      ], 404);
    }

    $lvl = max(1, (int) $character->current_level);

    // Existing feats from CharacterFeat
    $feats = \App\Models\CharacterFeat::where('character_id', $character->id)
      ->orWhere('character_name', $character->name)
      ->orderBy('level')
      ->get()
      ->map(function ($f) {
        return [
          'level' => (int) $f->level,
          'feat_name' => $f->feat_name,
          'feat_type' => $f->feat_type ?: 'General',
          'notes' => $f->notes,
        ];
      });

    // Check if character_info or character_profile exists
    $info = \App\Models\CharacterInfo::where('character_id', $character->id)->first();
    $profile = \App\Models\CharacterProfile::where('character_name', $character->name)->first();

    // Required odd-level feat slots: 1, 3, 5, ..., up to $lvl
    $requiredFeatLevels = [];
    for ($i = 1; $i <= $lvl; $i += 2) {
      $requiredFeatLevels[] = $i;
    }

    $suggestedClass = 'Fighter';
    $suggestedArchetype = '';

    if ($info && !empty($info->class_and_level)) {
      $cleanClassAndLvl = trim($info->class_and_level);
      if (strpos($cleanClassAndLvl, '(') !== false) {
        $parts = explode('(', $cleanClassAndLvl);
        $suggestedClass = trim($parts[0]);
        $suggestedArchetype = trim(str_replace(')', '', $parts[1]));
      } else {
        $suggestedClass = explode(' ', $cleanClassAndLvl)[0];
      }
    } elseif ($profile && !empty($profile->class_name)) {
      $suggestedClass = $profile->class_name;
      $suggestedArchetype = $profile->archetype ?: '';
    } elseif ($feats->contains(function ($f) {
      return stripos($f['feat_name'], 'Discovery') !== false || stripos($f['feat_name'], 'Extract') !== false || stripos($f['feat_name'], 'Brew Potion') !== false;
    })) {
      $suggestedClass = 'Alchemist';
    } elseif ($feats->contains(function ($f) {
      return stripos($f['feat_name'], 'Sneak Attack') !== false || stripos($f['feat_name'], 'Rogue Talent') !== false;
    })) {
      $suggestedClass = 'Rogue';
    } elseif ($character->rank && strcasecmp($character->rank->name, 'Clerk') === 0) {
      $suggestedClass = 'Expert';
    }

    return response()->json([
      'success' => true,
      'character' => [
        'id' => $character->id,
        'name' => $character->name,
        'level' => $lvl,
        'total_xp' => (float) $character->total_xp,
        'rank' => $character->rank?->name ?? 'Üye',
        'character_type' => $character->character_type ?: 'MAIN',
        'allowance_sp' => (float) ($character->allowance_sp ?? 0),
        'suggested_race' => $info?->race ?: ($profile?->race ?: 'Human'),
        'suggested_class' => $suggestedClass,
        'suggested_archetype' => $suggestedArchetype ?: ($profile?->archetype ?: ''),
        'suggested_stats' => [
          'str' => (int) ($info?->strength ?: ($profile?->str ?: 14)),
          'dex' => (int) ($info?->dexterity ?: ($profile?->dex ?: 14)),
          'con' => (int) ($info?->constitution ?: ($profile?->con ?: 14)),
          'int' => (int) ($info?->intelligence ?: ($profile?->int ?: 14)),
          'wis' => (int) ($info?->wisdom ?: ($profile?->wis ?: 10)),
          'cha' => (int) ($info?->charisma ?: ($profile?->cha ?: 10)),
        ],
        'existing_feats' => $feats,
        'required_feat_levels' => $requiredFeatLevels,
      ],
    ]);
  }

  /**
   * Get all Pathfinder traits from enum catalog.
   */
  public function getTraits()
  {
    $traitsFile = file_exists(config_path('enums/traits.php'))
      ? config_path('enums/traits.php')
      : (file_exists(config_path('enums/pathfinder_traits.php')) ? config_path('enums/pathfinder_traits.php') : null);
    $traits = $traitsFile ? require $traitsFile : [];

    return response()->json([
      'success' => true,
      'traits' => $traits,
      'total' => count($traits),
    ]);
  }

  /**
   * Claim an existing character from the XP registry at their real level.
   * Enforces mandatory Class, Traits, and Feats up to their current level.
   */
  public function claimCharacter(Request $request)
  {
    $user = Auth::user();
    if (!$user) {
      return response()->json([
        'success' => false,
        'message' => __('Please sign in to claim a character.'),
      ], 401);
    }

    $existingCount = \App\Models\CharacterProfile::where('user_id', $user->id)->count();
    if ($existingCount >= 2) {
      return response()->json([
        'success' => false,
        'message' => __('Character limit reached. Each player can have at most 2 characters (1 Main and 1 Clerk).'),
      ], 422);
    }

    $validated = $request->validate([
      'character_id' => ['required'],
      'race' => ['required', 'string', 'max:50'],
      'class_name' => ['required', 'string', 'max:50'],
      'archetype' => ['nullable', 'string', 'max:50'],
      'str' => ['required', 'integer', 'min:1', 'max:30'],
      'dex' => ['required', 'integer', 'min:1', 'max:30'],
      'con' => ['required', 'integer', 'min:1', 'max:30'],
      'int' => ['required', 'integer', 'min:1', 'max:30'],
      'wis' => ['required', 'integer', 'min:1', 'max:30'],
      'cha' => ['required', 'integer', 'min:1', 'max:30'],
      'traits' => ['nullable', 'array'],
      'traits.trait_1_name' => ['nullable', 'string', 'max:100'],
      'traits.trait_1_desc' => ['nullable', 'string', 'max:500'],
      'traits.trait_2_name' => ['nullable', 'string', 'max:100'],
      'traits.trait_2_desc' => ['nullable', 'string', 'max:500'],
      'traits.trait_3_name' => ['nullable', 'string', 'max:100'],
      'traits.trait_3_desc' => ['nullable', 'string', 'max:500'],
      'traits.rank_trait_name' => ['nullable', 'string', 'max:100'],
      'traits.rank_trait_desc' => ['nullable', 'string', 'max:500'],
      'feats' => ['nullable', 'array'],
    ]);

    // Find the character from characters table
    $character = \App\Models\Character::with('rank')
      ->where('id', is_numeric($validated['character_id']) ? (int)$validated['character_id'] : 0)
      ->orWhere('name', $validated['character_id'])
      ->first();

    if (!$character) {
      return response()->json([
        'success' => false,
        'message' => __('Selected character was not found in the XP registry.'),
      ], 404);
    }

    $charRankType = ($character->character_type === 'CLERK' || strcasecmp($character->rank?->name ?? '', 'Clerk') === 0) ? 'Clerk' : 'Main';

    // Check if user already has this rank type
    $rankTaken = \App\Models\CharacterProfile::where('user_id', $user->id)
      ->where(function ($q) use ($charRankType) {
        $q->where('rank', $charRankType)
          ->orWhere('character_type', $charRankType);
      })
      ->exists();

    if ($rankTaken) {
      return response()->json([
        'success' => false,
        'message' => __("You already have a :rank character. You can only own one Main and one Clerk.", ['rank' => $charRankType]),
      ], 422);
    }

    $lvl = max(1, (int) $character->current_level);

    // Determine class hit die and starting HP at $lvl
    $loadEnum = function ($file) {
      $p1 = config_path('enums/' . $file);
      if (file_exists($p1)) return require $p1;
      $p2 = config_path('Enums/' . $file);
      if (file_exists($p2)) return require $p2;
      return [];
    };
    $pfClasses = $loadEnum('pathfinder_classes.php');
    $classKey = strtolower($validated['class_name']);
    $clsInfo = $pfClasses[$classKey] ?? [];
    $hitDie = $clsInfo['hit_die'] ?? 'd10';
    $hdNumber = (int) filter_var($hitDie, FILTER_SANITIZE_NUMBER_INT) ?: 10;
    $conMod = (int) floor(($validated['con'] - 10) / 2);

    // Standard Pathfinder HP calculation for level $lvl
    $firstLevelHp = max(1, $hdNumber + $conMod);
    $perLevelHp = max(1, (int)ceil($hdNumber / 2) + 1 + $conMod);
    $totalMaxHp = $firstLevelHp + max(0, $lvl - 1) * $perLevelHp;

    // BAB and Saves at $lvl
    $babConfig = $loadEnum('bab.php');
    $progressionsConfig = $pfClasses;
    $babTableKey = $clsInfo['bab_table'] ?? 'bab_section_1';
    $bab = $babConfig[$babTableKey][$lvl] ?? '+1';
    $classSaves = $progressionsConfig[$classKey]['saves'][$lvl] ?? ['fort' => '+2', 'ref' => '+0', 'will' => '+0'];
    $castingStat = $progressionsConfig[$classKey]['casting_stat'] ?? 'none';

    // Format traits (3 slots: Trait 1 / Üye, Trait 2 / Kıdemli, Trait 3 / Uzman)
    $traitsInput = $validated['traits'] ?? [];
    $t1Name = trim($traitsInput['trait_1_name'] ?? ($traitsInput['uye']['name'] ?? ''));
    $t1Desc = trim($traitsInput['trait_1_desc'] ?? ($traitsInput['uye']['desc'] ?? ''));
    $t2Name = trim($traitsInput['trait_2_name'] ?? ($traitsInput['kidemli']['name'] ?? ''));
    $t2Desc = trim($traitsInput['trait_2_desc'] ?? ($traitsInput['kidemli']['desc'] ?? ''));
    $t3Name = trim($traitsInput['trait_3_name'] ?? ($traitsInput['rank_trait_name'] ?? ($traitsInput['uzman']['name'] ?? '')));
    $t3Desc = trim($traitsInput['trait_3_desc'] ?? ($traitsInput['rank_trait_desc'] ?? ($traitsInput['uzman']['desc'] ?? '')));

    $formattedTraits = [
      'trait_1' => [
        'name' => $t1Name,
        'desc' => $t1Desc,
      ],
      'trait_2' => [
        'name' => $t2Name,
        'desc' => $t2Desc,
      ],
      'trait_3' => [
        'name' => $t3Name,
        'desc' => $t3Desc,
      ],
      'rank_trait' => [
        'name' => $t3Name,
        'desc' => $t3Desc,
      ],
      'uye' => [
        'name' => $t1Name,
        'desc' => $t1Desc,
      ],
      'kidemli' => [
        'name' => $t2Name,
        'desc' => $t2Desc,
      ],
      'uzman' => [
        'name' => $t3Name,
        'desc' => $t3Desc,
      ],
    ];

    // Format feats
    $formattedFeats = [];
    if (!empty($validated['feats']) && is_array($validated['feats'])) {
      foreach ($validated['feats'] as $lvlKey => $fVal) {
        $fName = is_array($fVal) ? ($fVal['name'] ?? '') : (string)$fVal;
        $fType = is_array($fVal) ? ($fVal['type'] ?? 'General') : 'General';
        if (!empty(trim($fName))) {
          $formattedFeats[] = [
            'level' => (int) $lvlKey,
            'name' => trim($fName),
            'type' => $fType,
          ];
          // Also persist/update in CharacterFeat
          \App\Models\CharacterFeat::updateOrCreate(
            [
              'character_id' => $character->id,
              'level' => (int) $lvlKey,
            ],
            [
              'character_name' => $character->name,
              'feat_name' => trim($fName),
              'feat_type' => $fType,
            ]
          );
        }
      }
    } else {
      // Pull existing feats if any already exist for this character
      $existingFeats = \App\Models\CharacterFeat::where('character_id', $character->id)->get();
      foreach ($existingFeats as $ef) {
        $formattedFeats[] = [
          'level' => (int) $ef->level,
          'name' => $ef->feat_name,
          'type' => $ef->feat_type ?? 'General',
        ];
      }
    }

    $nextXpThreshold = \App\Models\LevelThreshold::where('level', '>', $lvl)->orderBy('level')->value('required_xp') ?? ((float)$character->total_xp + 50000);

    // Initial sheet data at real level!
    $sheetData = [
      'charId' => (string) $character->id,
      'name' => $character->name,
      'player' => $user->name,
      'alignment' => 'Neutral Good (NG)',
      'level' => $lvl,
      'classKey' => $classKey,
      'className' => $clsInfo['name'] ?? ucfirst($validated['class_name']),
      'archetype' => $validated['archetype'] ?? '',
      'race' => $validated['race'],
      'rank' => $charRankType,
      'character_type' => $charRankType,
      'guild_rank' => $character->rank?->name ?? 'Üye',
      'size' => 'Medium',
      'str' => $validated['str'],
      'dex' => $validated['dex'],
      'con' => $validated['con'],
      'int' => $validated['int'],
      'wis' => $validated['wis'],
      'cha' => $validated['cha'],
      'abilityScores' => [
        'str' => $validated['str'],
        'dex' => $validated['dex'],
        'con' => $validated['con'],
        'int' => $validated['int'],
        'wis' => $validated['wis'],
        'cha' => $validated['cha'],
      ],
      'currentHp' => $totalMaxHp,
      'maxHp' => $totalMaxHp,
      'hitDie' => $hitDie,
      'bab' => $bab,
      'fortBase' => (int) filter_var($classSaves['fort'] ?? '+2', FILTER_SANITIZE_NUMBER_INT),
      'refBase' => (int) filter_var($classSaves['ref'] ?? '+0', FILTER_SANITIZE_NUMBER_INT),
      'willBase' => (int) filter_var($classSaves['will'] ?? '+0', FILTER_SANITIZE_NUMBER_INT),
      'acDex' => (int) floor(($validated['dex'] - 10) / 2),
      'feats' => $formattedFeats,
      'traits' => $formattedTraits,
      'currentXp' => (float) $character->total_xp,
      'nextLevelXp' => (float) $nextXpThreshold,
      'money' => ['cp' => 0, 'sp' => (float)($character->allowance_sp ?? 0), 'gp' => 150 + round(($character->allowance_sp ?? 0)/10, 2), 'pp' => 0],
    ];

    // Create or update CharacterProfile
    $profile = \App\Models\CharacterProfile::updateOrCreate(
      ['character_name' => $character->name],
      [
        'character_id' => (string) $character->id,
        'user_id' => $user->id,
        'player_name' => $user->name,
        'race' => $validated['race'],
        'class_name' => $clsInfo['name'] ?? ucfirst($validated['class_name']),
        'archetype' => $validated['archetype'] ?? '',
        'level' => $lvl,
        'rank' => $charRankType,
        'character_type' => $charRankType,
        'str' => $validated['str'],
        'dex' => $validated['dex'],
        'con' => $validated['con'],
        'int' => $validated['int'],
        'wis' => $validated['wis'],
        'cha' => $validated['cha'],
        'hp' => $totalMaxHp,
        'max_hp' => $totalMaxHp,
        'hit_die' => $hitDie,
        'bab' => $bab,
        'fort_save' => $classSaves['fort'] ?? '+2',
        'ref_save' => $classSaves['ref'] ?? '+0',
        'will_save' => $classSaves['will'] ?? '+0',
        'sheet_data' => $sheetData,
      ]
    );

    \Illuminate\Support\Facades\DB::transaction(function () use ($character, $user) {
      $previousOwnerId = $character->user_id;
      $character->user_id = $user->id;
      $character->status = 'ACTIVE';
      $components = (float) $character->rank_floor_xp + (float) $character->quest_xp
        + (float) $character->report_xp + (float) $character->bailiff_xp
        + (float) $character->ex_char_xp + (float) $character->wiki_xp;
      if (($components > 0 && abs($components - (float) $character->total_xp) > 0.001)
        || ($character->total_xp > 0 && \App\Models\LevelThreshold::calculateLevelFromXp($character->total_xp) !== $character->current_level)) {
        $character->saveQuietly();
      } else {
        $character->save();
      }

      \App\Models\PlayerXp::where('character_name', $character->name)
        ->where('user_id', $previousOwnerId)
        ->update(['user_id' => $user->id, 'player_name' => $user->display_name]);

      $user->current_character = $character->name;
      $user->save();
    });

    return response()->json([
      'success' => true,
      'message' => __(':name has been claimed and initialized at Level :level with :xp XP!', [
        'name' => $character->name,
        'level' => $lvl,
        'xp' => number_format($character->total_xp, 2)
      ]),
      'character' => $profile,
    ]);
  }
}