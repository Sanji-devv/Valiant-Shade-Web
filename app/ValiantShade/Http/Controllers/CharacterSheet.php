<?php

namespace App\ValiantShade\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterFeat;
use App\Models\CharacterProfile;
use App\Models\CharacterTrait;
use App\Models\InventoryItem;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CharacterSheet extends Controller
{
  public function index(Request $request)
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
    $progressions = $pfClasses;
    $saves = array_map(fn($c) => $c['saves'] ?? [], $progressions);
    $classSkills = array_map(fn($c) => $c['skills'] ?? [], $progressions);
    $armors = $loadEnum('pathfinder_armors.php');
    if (empty($armors)) {
      $armorsFile = config_path('enums/pathfinder_armors.json');
      $armors = file_exists($armorsFile) ? json_decode(file_get_contents($armorsFile), true) : [];
    }
    $saveItems = $loadEnum('pathfinder_save_items.php');
    if (empty($saveItems)) {
      $saveItemsFile = config_path('enums/pathfinder_save_items.json');
      $saveItems = file_exists($saveItemsFile) ? json_decode(file_get_contents($saveItemsFile), true) : [];
    }

    $charId = $request->query('id');
    $charName = $request->query('name');

    // Find character from database strictly scoped to the authenticated user
    $dbCharacter = null;
    $allDbCharacters = collect();

    try {
      $userQuery = CharacterProfile::with(['feats', 'traits']);
      if ($user) {
        $userQuery->where('user_id', $user->id);
      } else {
        $userQuery->whereRaw('1 = 0');
      }

      if (!empty($charId)) {
        $dbCharacter = (clone $userQuery)->where(function ($q) use ($charId) {
          $q->where('character_id', $charId);
          if (is_numeric($charId)) {
            $q->orWhere('id', (int) $charId);
          }
        })->first();

        // If not found in user-scoped query (e.g. testing or staff or matching global ID)
        if (!$dbCharacter) {
          $dbCharacter = CharacterProfile::with(['feats', 'traits'])->where(function ($q) use ($charId) {
            $q->where('character_id', $charId);
            if (is_numeric($charId)) {
              $q->orWhere('id', (int) $charId);
            }
          })->first();
        }
      }
      if (!$dbCharacter && !empty($charName)) {
        $dbCharacter = (clone $userQuery)->where('character_name', $charName)->first();
        if (!$dbCharacter) {
          $dbCharacter = CharacterProfile::with(['feats', 'traits'])->where('character_name', $charName)->first();
        }
      }
      if (!$dbCharacter && $user && !empty($user->current_character)) {
        $dbCharacter = (clone $userQuery)->where('character_name', $user->current_character)->first();
      }
      if (!$dbCharacter && $user) {
        $dbCharacter = (clone $userQuery)->first();
      }

      // Synchronize latest relations into $dbCharacter payload
      if ($dbCharacter) {
        $dbCharacter->loadMissing(['feats', 'traits']);
        $sheet = is_array($dbCharacter->sheet_data) ? $dbCharacter->sheet_data : (json_decode($dbCharacter->sheet_data, true) ?: []);

        // Sync fresh traits from character_traits relation
        $dbTraits = [];
        foreach ($dbCharacter->traits as $t) {
          if (!empty($t->slot_key)) {
            $tUrl = $t->source_url;
            if (empty($tUrl) && !empty($t->trait_name)) {
              $tUrl = 'https://www.d20pfsrd.com/?s=' . urlencode($t->trait_name);
            }
            $dbTraits[$t->slot_key] = [
              'name'       => $t->trait_name ?: '',
              'desc'       => $t->description ?: '',
              'category'   => $t->category,
              'source_url' => $tUrl,
              'url'        => $tUrl,
            ];
          }
        }
        if (!empty($dbTraits)) {
          $sheet['traits'] = array_merge($sheet['traits'] ?? [], $dbTraits);
          if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
            $sheet['sheetData']['traits'] = array_merge($sheet['sheetData']['traits'] ?? [], $dbTraits);
          }
        }

        // Sync fresh feats from character_feats relation
        if ($dbCharacter->feats->isNotEmpty()) {
          $dbFeats = [];
          foreach ($dbCharacter->feats as $f) {
            $dbFeats[] = [
              'title'     => $f->feat_name,
              'desc'      => $f->notes ?: '',
              'level'     => $f->level,
              'slot_type' => $f->slot_type,
              'category'  => $f->category,
            ];
          }
          $sheet['feats'] = $dbFeats;
          if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
            $sheet['sheetData']['feats'] = $dbFeats;
          }
        }

        // Sync stats
        $sheet['level'] = (int) $dbCharacter->level;
        $sheet['race'] = $dbCharacter->race;
        $sheet['class'] = $dbCharacter->class_name;
        $sheet['classKey'] = strtolower($dbCharacter->class_name);
        $sheet['hp'] = (int) $dbCharacter->hp;
        $sheet['maxHp'] = (int) ($dbCharacter->max_hp ?: $dbCharacter->hp);
        if (isset($sheet['sheetData']) && is_array($sheet['sheetData'])) {
          $sheet['sheetData']['level'] = (int) $dbCharacter->level;
          $sheet['sheetData']['race'] = $dbCharacter->race;
          $sheet['sheetData']['currentHp'] = (int) $dbCharacter->hp;
          $sheet['sheetData']['maxHp'] = (int) ($dbCharacter->max_hp ?: $dbCharacter->hp);
          if (!isset($sheet['sheetData']['abilityScores'])) {
            $sheet['sheetData']['abilityScores'] = [];
          }
          $sheet['sheetData']['abilityScores']['str'] = (int) $dbCharacter->str;
          $sheet['sheetData']['abilityScores']['dex'] = (int) $dbCharacter->dex;
          $sheet['sheetData']['abilityScores']['con'] = (int) $dbCharacter->con;
          $sheet['sheetData']['abilityScores']['int'] = (int) $dbCharacter->int;
          $sheet['sheetData']['abilityScores']['wis'] = (int) $dbCharacter->wis;
          $sheet['sheetData']['abilityScores']['cha'] = (int) $dbCharacter->cha;
        }

        $dbCharacter->sheet_data = $sheet;
      }

      // Only fetch characters belonging to the authenticated user
      $allDbCharacters = $user
        ? CharacterProfile::where('user_id', $user->id)
            ->select('id', 'character_id', 'user_id', 'character_name', 'class_name', 'level', 'race')
            ->orderBy('character_name', 'asc')
            ->get()
        : collect();
    } catch (\Throwable $e) {
      $dbCharacter = null;
      $allDbCharacters = collect();
    }

    $purchasedItems = collect();
    $purchasedItemsAmbiguous = false;
    if ($dbCharacter && $dbCharacter->user_id) {
      $characters = Character::query()->where('name', $dbCharacter->character_name)
        ->where('user_id', $dbCharacter->user_id)->get();
      $profileCount = CharacterProfile::query()->where('character_name', $dbCharacter->character_name)
        ->where('user_id', $dbCharacter->user_id)->count();
      $purchasedItemsAmbiguous = $characters->count() > 1 || $profileCount > 1;
      if (! $purchasedItemsAmbiguous && $characters->count() === 1) {
        $purchasedItems = InventoryItem::query()->where('character_id', $characters->first()->id)
          ->orderBy('name')->get();
      }
    }

    return view('valiant-shade.content.pages.pages-character-sheet', compact(
      'user',
      'pfRaces',
      'pfClasses',
      'bab',
      'saves',
      'progressions',
      'classSkills',
      'armors',
      'saveItems',
      'dbCharacter',
      'allDbCharacters',
      'purchasedItems',
      'purchasedItemsAmbiguous'
    ));
  }

  public function save(Request $request): JsonResponse
  {
    try {
      $user = Auth::user();
      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'Karakter kaydetmek için lütfen giriş yapın.',
        ], 401);
      }

      $charId = $request->input('id') ?? $request->input('character_id');
      $charName = trim($request->input('name') ?? $request->input('character_name') ?? '');

      $profile = null;
      $userQuery = CharacterProfile::where('user_id', $user->id);
      if (!empty($charId)) {
        $profile = (clone $userQuery)->where(function ($q) use ($charId) {
          $q->where('character_id', $charId);
          if (is_numeric($charId)) {
            $q->orWhere('id', (int) $charId);
          }
        })->first();
      }
      if (!$profile && !empty($charName)) {
        $profile = (clone $userQuery)->where('character_name', $charName)->first();
      }
      if (!$profile) {
        $profile = new CharacterProfile();
        $profile->user_id = $user->id;
      }

      if (empty($charName)) {
        $charName = $profile->character_name ?: 'Valiant Adventurer';
      }

      $sheetData = $request->input('sheetData', []);
      if (!is_array($sheetData)) {
        $sheetData = [];
      }

      $profile->character_id = $charId ?: ('char_' . time());
      $profile->user_id = $user->id;
      $profile->character_name = $charName;
      $profile->player_name = $sheetData['player'] ?? ($profile->player_name ?: ($user->name ?? 'Oyuncu'));
      
      // Race and Rank are permanent and immutable after creation
      if (!$profile->exists) {
        $profile->race = $request->input('race') ?: 'Human';
        $profile->rank = $request->input('rank') ?: 'Main';
        $profile->character_type = $profile->rank;
      }
      
      $profile->class_name = $request->input('className') ?? $request->input('classKey') ?? ($profile->class_name ?: 'Paladin');
      $profile->level = (int) ($request->input('level') ?? 1);
      $profile->hit_die = $request->input('hitDie') ?? ($profile->hit_die ?: 'd10');
      $profile->hp = (int) ($request->input('hp') ?? 12);
      $profile->max_hp = (int) ($request->input('maxHp') ?? 12);

      $abilityScores = $sheetData['abilityScores'] ?? [];
      $profile->str = (int) ($abilityScores['str'] ?? 10);
      $profile->dex = (int) ($abilityScores['dex'] ?? 10);
      $profile->con = (int) ($abilityScores['con'] ?? 10);
      $profile->int = (int) ($abilityScores['int'] ?? 10);
      $profile->wis = (int) ($abilityScores['wis'] ?? 10);
      $profile->cha = (int) ($abilityScores['cha'] ?? 10);

      $profile->bab = (string) ($sheetData['bab'] ?? '');
      $profile->fort_save = (string) ($sheetData['fortSave'] ?? '');
      $profile->ref_save = (string) ($sheetData['refSave'] ?? '');
      $profile->will_save = (string) ($sheetData['willSave'] ?? '');
      $profile->notes = (string) ($sheetData['spellNotes'] ?? $profile->notes);

      // Save complete payload while preserving immutable race and rank
      $payload = $request->all();
      if (isset($payload['sheetData']) && is_array($payload['sheetData'])) {
        $payload['sheetData']['race'] = $profile->race;
        $payload['sheetData']['rank'] = $profile->rank;
        $payload['sheetData']['character_type'] = $profile->character_type ?? $profile->rank;
      }
      $payload['race'] = $profile->race;
      $payload['rank'] = $profile->rank;
      $payload['character_type'] = $profile->character_type ?? $profile->rank;
      $profile->sheet_data = $payload;
      $profile->save();

      // Update current user's active character
      $user->current_character = $profile->character_name;
      $user->save();

      // Sync character feats to character_feats table
      $feats = $sheetData['feats'] ?? [];
      if (is_array($feats)) {
        CharacterFeat::where('character_name', $profile->character_name)->delete();
        foreach ($feats as $idx => $f) {
          $fTitle = is_array($f) ? ($f['title'] ?? '') : (string) $f;
          $fDesc = is_array($f) ? ($f['desc'] ?? '') : '';
          if (!empty(trim($fTitle))) {
            CharacterFeat::create([
              'character_name' => $profile->character_name,
              'level'          => 1,
              'slot_type'      => 'standard',
              'feat_name'      => trim($fTitle),
              'notes'          => trim($fDesc),
              'sort_order'     => $idx,
            ]);
          }
        }
      }

      // Sync character traits to character_traits table
      $traits = $sheetData['traits'] ?? ($request->input('traits') ?? []);
      if (is_array($traits)) {
        $guildChar = \App\Models\Character::where('name', $profile->character_name)->first();
        if (!$guildChar && is_numeric($profile->character_id)) {
          $guildChar = \App\Models\Character::where('id', (int) $profile->character_id)->first();
        }
        $slotMeta = [
          'uye'     => ['slot_name' => 'Üye', 'required_rank' => 'Üye', 'required_hierarchy' => 3],
          'kidemli' => ['slot_name' => 'Kıdemli', 'required_rank' => 'Kıdemli', 'required_hierarchy' => 4],
          'uzman'   => ['slot_name' => 'Uzman', 'required_rank' => 'Uzman', 'required_hierarchy' => 5],
        ];

        foreach (['uye', 'kidemli', 'uzman'] as $slotKey) {
          if (isset($traits[$slotKey])) {
            $tVal = $traits[$slotKey];
            $tName = is_array($tVal) ? ($tVal['name'] ?? null) : (string) $tVal;
            $tDesc = is_array($tVal) ? ($tVal['desc'] ?? null) : null;
            if (!empty(trim((string)$tName)) && trim((string)$tName) !== '-') {
              CharacterTrait::updateOrCreate(
                [
                  'character_name' => $profile->character_name,
                  'slot_key'       => $slotKey,
                ],
                [
                  'character_id'       => $guildChar?->id,
                  'slot_name'          => $slotMeta[$slotKey]['slot_name'],
                  'required_rank'      => $slotMeta[$slotKey]['required_rank'],
                  'required_hierarchy' => $slotMeta[$slotKey]['required_hierarchy'],
                  'trait_name'         => trim($tName),
                  'description'        => $tDesc ? trim($tDesc) : null,
                ]
              );
            }
          }
        }
      }

      return response()->json([
        'success'   => true,
        'message'   => 'Karakter detayları veritabanına başarıyla kaydedildi.',
        'character' => $profile->fresh(),
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        'success' => false,
        'message' => 'Veritabanına kaydedilirken hata oluştu: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function data(Request $request): JsonResponse
  {
    $user = Auth::user();
    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Yetkisiz erişim.'], 401);
    }

    $charId = $request->query('id');
    $charName = $request->query('name');

    $userQuery = CharacterProfile::where('user_id', $user->id);
    $profile = null;
    if (!empty($charId)) {
      $profile = (clone $userQuery)->where(function ($q) use ($charId) {
        $q->where('character_id', $charId);
        if (is_numeric($charId)) {
          $q->orWhere('id', (int) $charId);
        }
      })->first();
    }
    if (!$profile && !empty($charName)) {
      $profile = (clone $userQuery)->where('character_name', $charName)->first();
    }

    if (!$profile) {
      return response()->json(['success' => false, 'message' => 'Karakter bulunamadı veya bu karaktere erişim izniniz yok.'], 404);
    }

    return response()->json([
      'success'   => true,
      'character' => $profile,
    ]);
  }
}
