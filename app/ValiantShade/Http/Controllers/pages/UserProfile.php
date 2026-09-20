<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfile extends Controller
{
  public function index()
  {
    $user = Auth::user();
    $loadEnum = function ($file) {
      $p1 = config_path('enums/' . $file);
      if (file_exists($p1)) return require $p1;
      $p2 = config_path('Enums/' . $file);
      if (file_exists($p2)) return require $p2;
      return [];
    };

    $pfRaces = $loadEnum('pathfinder_races.php');
    $pfClasses = $loadEnum('pathfinder_classes.php');
    $bab = $loadEnum('bab.php');
    $saves = $loadEnum('classes_save.php');
    $progressions = $loadEnum('class_progressions.php');

    $vault = \App\Models\GuildVault::firstOrCreate(
      ['name' => 'Ana Lonca Kasası'],
      ['balance_sp' => 358989.50, 'notes' => 'Valiant Shade lonca ana kasası']
    );

    $characters = $user
      ? \App\Models\CharacterProfile::where('user_id', $user->id)
          ->orderByRaw("CASE WHEN rank = 'Main' OR character_type = 'Main' THEN 1 WHEN rank = 'Clerk' OR character_type = 'Clerk' THEN 2 ELSE 3 END")
          ->orderBy('id', 'asc')
          ->get()
      : collect();

    $charactersData = $characters->map(function ($c) use ($user) {
      return [
        'id' => $c->character_id ?: (string) $c->id,
        'db_id' => $c->id,
        'name' => $c->character_name,
        'player' => $c->player_name ?: ($user->name ?? 'Player'),
        'race' => $c->race,
        'raceKey' => strtolower($c->race ?? ''),
        'class' => $c->class_name,
        'classKey' => strtolower($c->class_name ?? ''),
        'rank' => $c->rank ?: 'Main',
        'character_type' => $c->character_type ?: ($c->rank ?: 'Main'),
        'level' => $c->level ?: 1,
        'hp' => $c->hp ?: 10,
        'maxHp' => $c->max_hp ?: ($c->hp ?: 10),
        'hitDie' => $c->hit_die ?: 'd10',
        'str' => $c->str,
        'dex' => $c->dex,
        'con' => $c->con,
        'int' => $c->int,
        'wis' => $c->wis,
        'cha' => $c->cha,
        'gold' => ($c->sheet_data['money']['gp'] ?? 150),
        'active' => ($user && $user->current_character === $c->character_name),
      ];
    })->values();

    return view('valiant-shade.content.pages.pages-profile-user', compact(
      'user',
      'characters',
      'charactersData',
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

    $existingCount = \App\Models\CharacterProfile::where('user_id', $user->id)->count();
    if ($existingCount >= 2) {
      return response()->json([
        'success' => false,
        'message' => __('Character limit reached. Each player can have at most 2 characters (1 Main and 1 Clerk).'),
      ], 422);
    }

    $validated = $request->validate([
      'character_name' => ['required', 'string', 'min:2', 'max:50', 'unique:character_profiles,character_name'],
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

    // Check if user already has a character with this rank
    $rankTaken = \App\Models\CharacterProfile::where('user_id', $user->id)
      ->where(function ($q) use ($validated) {
        $q->where('rank', $validated['rank'])
          ->orWhere('character_type', $validated['rank']);
      })
      ->exists();

    if ($rankTaken) {
      return response()->json([
        'success' => false,
        'message' => __("You already have a :rank character. You can only create one Main and one Clerk.", ['rank' => $validated['rank']]),
      ], 422);
    }

    // Determine class hit die and starting HP
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
    $startingHp = max(1, $hdNumber + $conMod);

    // BAB and Saves at level 1
    $babConfig = $loadEnum('bab.php');
    $savesConfig = $loadEnum('classes_save.php');
    $progressionsConfig = $loadEnum('class_progressions.php');
    $babTableKey = $clsInfo['bab_table'] ?? 'bab_section_1';
    $bab = $babConfig[$babTableKey][1] ?? '+1';
    $classSaves = $savesConfig[$classKey][1] ?? ['fort' => '+2', 'ref' => '+0', 'will' => '+0'];
    $castingStat = $progressionsConfig[$classKey]['casting_stat'] ?? 'none';

    $initialSpecials = [];
    if (!empty($progressionsConfig[$classKey]['specials'][1])) {
      $specStr = $progressionsConfig[$classKey]['specials'][1];
      if ($specStr !== '—' && $specStr !== '-') {
        $parts = array_filter(array_map('trim', explode(',', $specStr)));
        $clsName = $clsInfo['name'] ?? ucfirst($validated['class_name']);
        foreach ($parts as $part) {
          $initialSpecials[] = [
            'badge' => 'Lvl 1',
            'title' => ucfirst($part),
            'desc' => "{$clsName} Lvl 1 Class Feature",
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
      'className' => $clsInfo['name'] ?? ucfirst($validated['class_name']),
      'race' => $validated['race'],
      'rank' => $validated['rank'],
      'character_type' => $validated['rank'],
      'size' => 'Medium',
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
      'tempHp' => 0,
      'nonLethal' => 0,
      'hitDie' => $hitDie,
      'speedBase' => 30,
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
      'race' => $validated['race'],
      'class_name' => $clsInfo['name'] ?? ucfirst($validated['class_name']),
      'level' => 1,
      'rank' => $validated['rank'],
      'character_type' => $validated['rank'],
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
}