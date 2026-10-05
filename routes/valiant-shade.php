<?php

use Illuminate\Support\Facades\Route;
use App\ValiantShade\Http\Controllers\dashboard\Analytics;
use App\ValiantShade\Http\Controllers\dashboard\Crm;
use App\ValiantShade\Http\Controllers\language\LanguageController;
use App\ValiantShade\Http\Controllers\pages\UserProfile;
use App\ValiantShade\Http\Controllers\pages\UserTeams;
use App\ValiantShade\Http\Controllers\pages\UserProjects;
use App\ValiantShade\Http\Controllers\pages\UserConnections;
use App\ValiantShade\Http\Controllers\pages\AccountSettingsAccount;
use App\ValiantShade\Http\Controllers\pages\CharacterSheet;
use App\ValiantShade\Http\Controllers\pages\Inventory;
use App\ValiantShade\Http\Controllers\pages\PlayerXpController;
use App\ValiantShade\Http\Controllers\authentications\LoginBasic;
use App\ValiantShade\Http\Controllers\authentications\RegisterBasic;
use App\ValiantShade\Http\Controllers\authentications\LogoutController;
use App\ValiantShade\Http\Controllers\apps\UserList;
use App\ValiantShade\Http\Controllers\apps\RoleController;
use App\ValiantShade\Http\Controllers\pages\NewsController;

/*
|--------------------------------------------------------------------------
| Valiant Shade Routes
|--------------------------------------------------------------------------
|
| Aktif kullanılan ve özelleştirilmiş rotalar.
|
*/

// Dashboards
Route::get('/', [Analytics::class, 'index'])->name('valiant-shade.dashboard-analytics');
Route::get('/dashboard/analytics', [Analytics::class, 'index'])->name('valiant-shade.dashboard-analytics');
Route::get('/dashboard/crm', [Crm::class, 'index'])->name('valiant-shade.dashboard-crm');

// Locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

// Pages
Route::get('/pages/profile-user', [UserProfile::class, 'index'])->name('valiant-shade.pages-profile-user');
Route::post('/pages/profile-user/vault-transfer', [UserProfile::class, 'vaultTransfer'])->name('valiant-shade.pages-profile-user.vault-transfer');
Route::get('/pages/profile-teams', [UserTeams::class, 'index'])->name('valiant-shade.pages-profile-teams');
Route::get('/pages/profile-projects', [UserProjects::class, 'index'])->name('valiant-shade.pages-profile-projects');
Route::get('/pages/profile-connections', [UserConnections::class, 'index'])->name('valiant-shade.pages-profile-connections');
Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('valiant-shade.pages-account-settings-account');
Route::get('/character/sheet', [CharacterSheet::class, 'index'])->name('valiant-shade.character-sheet');
Route::post('/character/sheet/save', [CharacterSheet::class, 'save'])->name('valiant-shade.character-sheet.save');
Route::get('/character/sheet/data', [CharacterSheet::class, 'data'])->name('valiant-shade.character-sheet.data');
Route::post('/character/create', [UserProfile::class, 'createCharacter'])->name('valiant-shade.character.create');
Route::get('/character/claimable-list', [UserProfile::class, 'getClaimableCharacters'])->name('valiant-shade.character.claimable-list');
Route::get('/character/claimable-details/{id}', [UserProfile::class, 'getClaimableCharacterDetails'])->name('valiant-shade.character.claimable-details');
Route::get('/character/traits', [UserProfile::class, 'getTraits'])->name('valiant-shade.character.traits');
Route::post('/character/claim', [UserProfile::class, 'claimCharacter'])->name('valiant-shade.character.claim');
Route::post('/character/delete/{id}', [UserProfile::class, 'deleteCharacter'])->name('valiant-shade.character.delete');
Route::delete('/character/{id}', [UserProfile::class, 'deleteCharacter'])->name('valiant-shade.character.destroy');
Route::post('/character/set-active', [UserProfile::class, 'setActiveCharacter'])->name('valiant-shade.character.set-active');
Route::get('/pages/inventory', [Inventory::class, 'index'])->name('valiant-shade.pages-inventory');
Route::post('/pages/inventory', [Inventory::class, 'store'])->name('valiant-shade.pages-inventory.store');
Route::put('/pages/inventory/{id}', [Inventory::class, 'update'])->name('valiant-shade.pages-inventory.update');
Route::delete('/pages/inventory/{id}', [Inventory::class, 'destroy'])->name('valiant-shade.pages-inventory.destroy');
Route::post('/pages/inventory/reset-excel', [Inventory::class, 'resetExcel'])->name('valiant-shade.pages-inventory.reset-excel');
Route::post('/pages/inventory/reset-defaults', [Inventory::class, 'resetExcel'])->name('valiant-shade.pages-inventory.reset-defaults');
Route::post('/pages/inventory/vault', [Inventory::class, 'updateVault'])->name('valiant-shade.pages-inventory.vault');

// News Routes
Route::get('/pages/news', [NewsController::class, 'index'])->name('valiant-shade.pages-news');
Route::get('/pages/news/{id}', [NewsController::class, 'show'])->name('valiant-shade.pages-news.show');
Route::post('/pages/news', [NewsController::class, 'store'])->name('valiant-shade.pages-news.store');
Route::post('/pages/news/reset-defaults', [NewsController::class, 'resetDefaults'])->name('valiant-shade.pages-news.reset-defaults');
Route::post('/pages/news/{id}', [NewsController::class, 'update'])->name('valiant-shade.pages-news.update');
Route::delete('/pages/news/{id}', [NewsController::class, 'destroy'])->name('valiant-shade.pages-news.destroy');
Route::post('/pages/news/{id}/toggle', [NewsController::class, 'toggleActive'])->name('valiant-shade.pages-news.toggle');
Route::redirect('/pages/guild-news', '/valiant-shade/pages/news');

// Player XP Tracker Routes
Route::get('/pages/xp-tracker', [PlayerXpController::class, 'index'])->name('valiant-shade.pages-xp-tracker');
Route::post('/pages/xp-tracker', [PlayerXpController::class, 'store'])->name('valiant-shade.pages-xp-tracker.store');
Route::put('/pages/xp-tracker/{id}', [PlayerXpController::class, 'update'])->name('valiant-shade.pages-xp-tracker.update');
Route::delete('/pages/xp-tracker/{id}', [PlayerXpController::class, 'destroy'])->name('valiant-shade.pages-xp-tracker.destroy');
Route::post('/pages/xp-tracker/{id}/quick-xp', [PlayerXpController::class, 'quickXp'])->name('valiant-shade.pages-xp-tracker.quick-xp');
Route::post('/pages/xp-tracker/batch-xp', [PlayerXpController::class, 'batchQuickXp'])->name('valiant-shade.pages-xp-tracker.batch-xp');
Route::post('/pages/xp-tracker/bulk-status', [PlayerXpController::class, 'bulkStatus'])->name('valiant-shade.pages-xp-tracker.bulk-status');
Route::post('/pages/xp-tracker/{id}/status', [PlayerXpController::class, 'changeStatus'])->name('valiant-shade.pages-xp-tracker.status');
Route::post('/pages/xp-tracker/reset-excel', [PlayerXpController::class, 'resetExcel'])->name('valiant-shade.pages-xp-tracker.reset-excel');
Route::post('/pages/xp-tracker/reset-defaults', [PlayerXpController::class, 'resetExcel'])->name('valiant-shade.pages-xp-tracker.reset-defaults');

// Adventurer Info (Executive Dashboard / Lonca Genel Özet) Routes
Route::get('/pages/adventurer-info', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'index'])->name('valiant-shade.pages-adventurer-info');
Route::get('/pages/maceraci-info', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'index'])->name('valiant-shade.pages-maceraci-info');
Route::get('/pages/guild-overview', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'index'])->name('valiant-shade.pages-guild-overview');

Route::post('/pages/adventurer-info/allowance', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'updateAllowance'])->name('valiant-shade.pages-adventurer-info.allowance');
Route::post('/pages/maceraci-info/allowance', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'updateAllowance'])->name('valiant-shade.pages-maceraci-info.allowance');

Route::put('/pages/adventurer-info/bailiff/{id}', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'updateBailiff'])->name('valiant-shade.pages-adventurer-info.bailiff');
Route::put('/pages/maceraci-info/bailiff/{id}', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'updateBailiff'])->name('valiant-shade.pages-maceraci-info.bailiff');

Route::post('/pages/adventurer-info/guild-level', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'updateGuildLevel'])->name('valiant-shade.pages-adventurer-info.guild-level');
Route::post('/pages/maceraci-info/guild-level', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'updateGuildLevel'])->name('valiant-shade.pages-maceraci-info.guild-level');

Route::post('/pages/adventurer-info/reset-excel', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-adventurer-info.reset-excel');
Route::post('/pages/adventurer-info/reset-defaults', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-adventurer-info.reset-defaults');
Route::post('/pages/maceraci-info/reset-excel', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-maceraci-info.reset-excel');
Route::post('/pages/maceraci-info/reset-defaults', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-maceraci-info.reset-defaults');

// Medieval Quest Board (Lonca Görev Panosu) Routes
Route::get('/pages/quest-board', [\App\ValiantShade\Http\Controllers\pages\QuestBoardController::class, 'index'])->name('valiant-shade.pages-quest-board');
Route::get('/pages/gorev-listesi', [\App\ValiantShade\Http\Controllers\pages\QuestBoardController::class, 'index'])->name('valiant-shade.pages-gorev-listesi');
Route::post('/pages/quest-board', [\App\ValiantShade\Http\Controllers\pages\QuestBoardController::class, 'store'])->name('valiant-shade.pages-quest-board.store');
Route::post('/pages/quest-board/{id}/status', [\App\ValiantShade\Http\Controllers\pages\QuestBoardController::class, 'updateStatus'])->name('valiant-shade.pages-quest-board.status');

// Quest Reports (Görev Raporları) Routes
Route::get('/pages/quest-reports', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'index'])->name('valiant-shade.pages-quest-reports');
Route::get('/pages/gorev-raporlari', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'index'])->name('valiant-shade.pages-gorev-raporlari');
Route::post('/pages/quest-reports', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'store'])->name('valiant-shade.pages-quest-reports.store');
Route::get('/pages/quest-reports/{id}', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'show'])->name('valiant-shade.pages-quest-reports.show');
Route::post('/pages/quest-reports/{id}/distribute-xp', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'distributeXp'])->name('valiant-shade.pages-quest-reports.distribute-xp');
Route::post('/pages/quest-reports/reset-defaults', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'resetDefaults'])->name('valiant-shade.pages-quest-reports.reset-defaults');
Route::post('/pages/quest-reports/reset-excel', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'resetExcel'])->name('valiant-shade.pages-quest-reports.reset-excel');
Route::get('/pages/quest-reports-pool', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'getGuildPool'])->name('valiant-shade.pages-quest-reports.guild-pool.get');
Route::post('/pages/quest-reports-pool/update', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'updateGuildPool'])->name('valiant-shade.pages-quest-reports.guild-pool.update');
Route::post('/pages/quest-reports-pool/reset', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'resetGuildPool'])->name('valiant-shade.pages-quest-reports.guild-pool.reset');
Route::post('/pages/quest-reports/guild-pool/update', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'updateGuildPool']);
Route::post('/pages/quest-reports/guild-pool/reset', [\App\ValiantShade\Http\Controllers\pages\QuestReportsController::class, 'resetGuildPool']);

// Pathfinder 1E Feat and Traits & Character Progression Routes
Route::get('/pages/featntrait', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'index'])->name('valiant-shade.pages-featntrait');
Route::get('/pages/feats', function (\Illuminate\Http\Request $request) {
    $qs = $request->getQueryString();
    return redirect()->to('/valiant-shade/pages/featntrait' . ($qs ? '?' . $qs : ''), 301);
})->name('valiant-shade.pages-feats-list');
Route::get('/pages/feats-list', function (\Illuminate\Http\Request $request) {
    $qs = $request->getQueryString();
    return redirect()->to('/valiant-shade/pages/featntrait' . ($qs ? '?' . $qs : ''), 301);
});
Route::get('/pages/feats-listesi', function (\Illuminate\Http\Request $request) {
    $qs = $request->getQueryString();
    return redirect()->to('/valiant-shade/pages/featntrait' . ($qs ? '?' . $qs : ''), 301);
});
Route::get('/pages/feats-and-traits', function (\Illuminate\Http\Request $request) {
    $qs = $request->getQueryString();
    return redirect()->to('/valiant-shade/pages/featntrait' . ($qs ? '?' . $qs : ''), 301);
})->name('valiant-shade.pages-feats-and-traits');

// Feat & Trait Action Routes (/pages/featntrait)
Route::post('/pages/featntrait', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'store'])->name('valiant-shade.pages-featntrait.store');
Route::put('/pages/featntrait/{id}', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'update'])->name('valiant-shade.pages-featntrait.update');
Route::delete('/pages/featntrait/{id}', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'destroy'])->name('valiant-shade.pages-featntrait.destroy');
Route::delete('/pages/featntrait/character/delete', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'deleteCharacter'])->name('valiant-shade.pages-featntrait.character.destroy');
Route::post('/pages/featntrait/stats', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'updateStats'])->name('valiant-shade.pages-featntrait.stats');
Route::post('/pages/featntrait/reset-excel', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-featntrait.reset-excel');
Route::post('/pages/featntrait/reset-defaults', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-featntrait.reset-defaults');

// Legacy Aliases (/pages/feats)
Route::post('/pages/feats', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'store'])->name('valiant-shade.pages-feats.store');
Route::put('/pages/feats/{id}', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'update'])->name('valiant-shade.pages-feats.update');
Route::delete('/pages/feats/{id}', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'destroy'])->name('valiant-shade.pages-feats.destroy');
Route::delete('/pages/feats/character/delete', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'deleteCharacter'])->name('valiant-shade.pages-feats.character.destroy');
Route::post('/pages/feats/stats', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'updateStats'])->name('valiant-shade.pages-feats.stats');
Route::post('/pages/feats/reset-excel', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-feats.reset-excel');
Route::post('/pages/feats/reset-defaults', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-feats.reset-defaults');

// Character Traits Routes (Guild Rank Progression: Üye, Kıdemli, Uzman)
Route::post('/pages/traits/save', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'saveTrait'])->name('valiant-shade.pages-traits.save');
Route::post('/pages/traits/clear', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'clearTrait'])->name('valiant-shade.pages-traits.clear');
Route::get('/pages/traits/search', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'searchTraits'])->name('valiant-shade.pages-traits.search');
Route::post('/pages/featntrait/traits/save', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'saveTrait'])->name('valiant-shade.pages-featntrait.traits.save');
Route::post('/pages/featntrait/traits/clear', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'clearTrait'])->name('valiant-shade.pages-featntrait.traits.clear');
Route::get('/pages/featntrait/traits/search', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'searchTraits'])->name('valiant-shade.pages-featntrait.traits.search');

// Authentication
Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('valiant-shade.auth-login-basic');
Route::post('/auth/login-basic', [LoginBasic::class, 'authenticate'])->name('valiant-shade.auth-login-basic.post');
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('valiant-shade.auth-register-basic');
Route::post('/auth/register-basic', [RegisterBasic::class, 'register'])->name('valiant-shade.auth-register-basic.post');
// Apps
Route::get('/app/user/list', [UserList::class, 'index'])->name('valiant-shade.app-user-list');
Route::get('/app/user/list-data', [UserList::class, 'data'])->name('valiant-shade.app-user-list.data');
Route::post('/app/user/create', [UserList::class, 'store'])->name('valiant-shade.app-user-list.store');
Route::delete('/app/user/{id}', [UserList::class, 'destroy'])->name('valiant-shade.app-user-list.destroy');
Route::delete('/app/user/{id}/character', [UserList::class, 'destroyCharacter'])->name('valiant-shade.app-user-list.destroy-character');
Route::post('/app/user/{id}/character/clear', [UserList::class, 'destroyCharacter'])->name('valiant-shade.app-user-list.clear-character');
Route::post('/app/user/sync-character', [UserList::class, 'syncCharacter'])->name('valiant-shade.app-user-list.sync-character');
Route::put('/app/user/{id}/role', [UserList::class, 'updateRole'])->name('valiant-shade.app-user-list.update-role');
Route::get('/app/roles', [RoleController::class, 'index'])->name('valiant-shade.app-roles.index');
Route::post('/app/roles', [RoleController::class, 'store'])->name('valiant-shade.app-roles.store');
Route::put('/app/roles/{role}', [RoleController::class, 'update'])->name('valiant-shade.app-roles.update');
Route::delete('/app/roles/{role}', [RoleController::class, 'destroy'])->name('valiant-shade.app-roles.destroy');
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
Route::post('/auth/logout', [LogoutController::class, 'logout'])->name('valiant-shade.auth-logout');


