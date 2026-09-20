<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\Models\CharacterFeat;
use App\Models\CharacterProfile;
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
    $saves = $loadEnum('classes_save.php');
    $progressions = $loadEnum('class_progressions.php');
    $classSkills = $loadEnum('pathfinder_class_skills.php');
    $armors = $loadEnum('pathfinder_armors.php');
    if (empty($armors)) {
      $armorsFile = config_path('enums/pathfinder_armors.json');
      $armors = file_exists($armorsFile) ? json_decode(file_get_contents($armorsFile), true) : [];
    }

    $charId = $request->query('id');
    $charName = $request->query('name');

    // Find character from database strictly scoped to the authenticated user
    $dbCharacter = null;
    $allDbCharacters = collect();

    try {
      $userQuery = CharacterProfile::with('feats');
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
      }
      if (!$dbCharacter && !empty($charName)) {
        $dbCharacter = (clone $userQuery)->where('character_name', $charName)->first();
      }
      if (!$dbCharacter && $user && !empty($user->current_character)) {
        $dbCharacter = (clone $userQuery)->where('character_name', $user->current_character)->first();
      }
      if (!$dbCharacter && $user) {
        $dbCharacter = (clone $userQuery)->first();
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

    return view('valiant-shade.content.pages.pages-character-sheet', compact(
      'user',
      'pfRaces',
      'pfClasses',
      'bab',
      'saves',
      'progressions',
      'classSkills',
      'armors',
      'dbCharacter',
      'allDbCharacters'
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

      $charId = $request->input('id');
      $charName = trim($request->input('name') ?? '');
      if (empty($charName)) {
        $charName = 'Valiant Adventurer';
      }

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
