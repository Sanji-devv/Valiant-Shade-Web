<?php

use App\Http\Controllers\Web\GodsPageController;
use App\ValiantShade\Http\Controllers\AccountSettingsAccount;
use App\ValiantShade\Http\Controllers\AdventurerInfoController;
use App\ValiantShade\Http\Controllers\apps\AuditLogController;
use App\ValiantShade\Http\Controllers\apps\HealthController;
use App\ValiantShade\Http\Controllers\apps\RoleController;
use App\ValiantShade\Http\Controllers\apps\UserList;
use App\ValiantShade\Http\Controllers\authentications\LoginBasic;
use App\ValiantShade\Http\Controllers\authentications\LogoutController;
use App\ValiantShade\Http\Controllers\authentications\RegisterBasic;
use App\ValiantShade\Http\Controllers\CharacterSheet;
use App\ValiantShade\Http\Controllers\dashboard\Analytics;
use App\ValiantShade\Http\Controllers\dashboard\Crm;
use App\ValiantShade\Http\Controllers\FeatsListController;
use App\ValiantShade\Http\Controllers\Inventory;
use App\ValiantShade\Http\Controllers\language\LanguageController;
use App\ValiantShade\Http\Controllers\NewsController;
use App\ValiantShade\Http\Controllers\PlayerXpController;
use App\ValiantShade\Http\Controllers\QuestBoardController;
use App\ValiantShade\Http\Controllers\QuestReportsController;
use App\ValiantShade\Http\Controllers\UserConnections;
use App\ValiantShade\Http\Controllers\UserProfile;
use App\ValiantShade\Http\Controllers\UserProjects;
use App\ValiantShade\Http\Controllers\UserTeams;
use App\ValiantShade\Http\Middleware\EnsureCanManageRoles;
use App\ValiantShade\Http\Middleware\EnsureUserIsAdmin;
use App\ValiantShade\Http\Middleware\LockQuestReportMutations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Valiant Shade Routes
|--------------------------------------------------------------------------
|
| Aktif kullanılan ve özelleştirilmiş rotalar.
|
*/

// prefix örneği
Route::prefix('/groupName')->group(function () {
    Route::get('/icsayfa', [Analytics::class, 'index'])->name('routeName');

});

// Dashboards
Route::get('/', [Analytics::class, 'index'])->name('valiant-shade.dashboard-analytics');
Route::get('/dashboard/analytics', [Analytics::class, 'index']);
Route::get('/dashboard/crm', [Crm::class, 'index'])->name('valiant-shade.dashboard-crm');

// Locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

// Pages

Route::get('/gods', [GodsPageController::class, 'index'])
    ->name('valiant-shade.gods.index');

Route::get('/gods/{slug}', [GodsPageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('valiant-shade.gods.show');

Route::get('/pages/profile-user', [UserProfile::class, 'index'])->name('valiant-shade.pages-profile-user');
Route::post('/pages/profile-user/vault-transfer', [UserProfile::class, 'vaultTransfer'])->name('valiant-shade.pages-profile-user.vault-transfer');
Route::get('/pages/profile-teams', [UserTeams::class, 'index'])->name('valiant-shade.pages-profile-teams');
Route::get('/pages/profile-projects', [UserProjects::class, 'index'])->name('valiant-shade.pages-profile-projects');
Route::get('/pages/profile-connections', [UserConnections::class, 'index'])->name('valiant-shade.pages-profile-connections');
Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('valiant-shade.pages-account-settings-account');
Route::post('/pages/account-settings-account', [AccountSettingsAccount::class, 'update'])->name('valiant-shade.pages-account-settings-account.update');
Route::post('/pages/account-settings-account/deactivate', [AccountSettingsAccount::class, 'deactivate'])->name('valiant-shade.pages-account-settings-account.deactivate');
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
Route::delete('/pages/inventory/bulk', [Inventory::class, 'destroyMany'])->name('valiant-shade.pages-inventory.bulk-destroy');
Route::post('/pages/inventory/sell', [Inventory::class, 'sellMany'])->name('valiant-shade.pages-inventory.sell');
Route::post('/pages/inventory', [Inventory::class, 'store'])->name('valiant-shade.pages-inventory.store');
Route::put('/pages/inventory/{id}', [Inventory::class, 'update'])->name('valiant-shade.pages-inventory.update');
Route::delete('/pages/inventory/{id}', [Inventory::class, 'destroy'])->name('valiant-shade.pages-inventory.destroy');
Route::post('/pages/inventory/reset-excel', [Inventory::class, 'resetExcel'])->name('valiant-shade.pages-inventory.reset-excel');
Route::post('/pages/inventory/reset-defaults', [Inventory::class, 'resetExcel'])->name('valiant-shade.pages-inventory.reset-defaults');
Route::post('/pages/inventory/vault', [Inventory::class, 'updateVault'])->name('valiant-shade.pages-inventory.vault');

// News Routes
Route::get('/pages/news', [NewsController::class, 'index'])->name('valiant-shade.pages-news');
Route::get('/pages/guild-news', [NewsController::class, 'index'])->name('valiant-shade.pages-guild-news');
Route::get('/pages/news/{id}', [NewsController::class, 'show'])->name('valiant-shade.pages-news.show');
Route::post('/pages/news', [NewsController::class, 'store'])->name('valiant-shade.pages-news.store');
Route::post('/pages/guild-news', [NewsController::class, 'store'])->name('valiant-shade.pages-guild-news.store');
Route::post('/pages/news/reset-defaults', [NewsController::class, 'resetDefaults'])->name('valiant-shade.pages-news.reset-defaults');
Route::post('/pages/guild-news/reset-defaults', [NewsController::class, 'resetDefaults'])->name('valiant-shade.pages-guild-news.reset-defaults');
Route::post('/pages/news/{id}', [NewsController::class, 'update'])->name('valiant-shade.pages-news.update');
Route::post('/pages/guild-news/{id}', [NewsController::class, 'update'])->name('valiant-shade.pages-guild-news.update');
Route::post('/pages/news/reorder', [NewsController::class, 'reorder'])->name('valiant-shade.pages-news.reorder');
Route::delete('/pages/news/{id}', [NewsController::class, 'destroy'])->name('valiant-shade.pages-news.destroy');
Route::delete('/pages/guild-news/{id}', [NewsController::class, 'destroy'])->name('valiant-shade.pages-guild-news.destroy');
Route::post('/pages/news/{id}/toggle', [NewsController::class, 'toggleActive'])->name('valiant-shade.pages-news.toggle');
Route::post('/pages/guild-news/{id}/toggle', [NewsController::class, 'toggleActive'])->name('valiant-shade.pages-guild-news.toggle');
Route::post('/pages/news/{id}/approve', [NewsController::class, 'approve'])->name('valiant-shade.pages-news.approve');
Route::post('/pages/news/{id}/reject', [NewsController::class, 'reject'])->name('valiant-shade.pages-news.reject');
Route::post('/pages/news/{id}/editorial-reaction', [NewsController::class, 'editorialReaction'])->name('valiant-shade.pages-news.editorial-reaction');

// Player XP Tracker Routes
Route::get('/pages/xp-tracker', [PlayerXpController::class, 'index'])->name('valiant-shade.pages-xp-tracker');
Route::post('/pages/xp-tracker', [PlayerXpController::class, 'store'])->name('valiant-shade.pages-xp-tracker.store');
Route::post('/pages/xp-tracker/import', [PlayerXpController::class, 'import'])->middleware(EnsureUserIsAdmin::class)->name('valiant-shade.pages-xp-tracker.import');
Route::post('/pages/xp-tracker/link-reports', [PlayerXpController::class, 'linkReports'])->middleware(EnsureUserIsAdmin::class)->name('valiant-shade.pages-xp-tracker.link-reports');
Route::delete('/pages/xp-tracker', [PlayerXpController::class, 'clear'])->middleware(EnsureUserIsAdmin::class)->name('valiant-shade.pages-xp-tracker.clear');
Route::put('/pages/xp-tracker/{id}', [PlayerXpController::class, 'update'])->name('valiant-shade.pages-xp-tracker.update');
Route::delete('/pages/xp-tracker/{id}', [PlayerXpController::class, 'destroy'])->name('valiant-shade.pages-xp-tracker.destroy');
Route::post('/pages/xp-tracker/{id}/quick-xp', [PlayerXpController::class, 'quickXp'])->name('valiant-shade.pages-xp-tracker.quick-xp');
Route::post('/pages/xp-tracker/batch-xp', [PlayerXpController::class, 'batchQuickXp'])->name('valiant-shade.pages-xp-tracker.batch-xp');
Route::post('/pages/xp-tracker/bulk-status', [PlayerXpController::class, 'bulkStatus'])->name('valiant-shade.pages-xp-tracker.bulk-status');
Route::post('/pages/xp-tracker/{id}/status', [PlayerXpController::class, 'changeStatus'])->name('valiant-shade.pages-xp-tracker.status');
Route::post('/pages/xp-tracker/reset-excel', [PlayerXpController::class, 'resetExcel'])->name('valiant-shade.pages-xp-tracker.reset-excel');
Route::post('/pages/xp-tracker/reset-defaults', [PlayerXpController::class, 'resetExcel'])->name('valiant-shade.pages-xp-tracker.reset-defaults');

// Adventurer Info (Executive Dashboard / Lonca Genel Özet) Routes
Route::get('/pages/adventurer-info', [AdventurerInfoController::class, 'index'])->name('valiant-shade.pages-adventurer-info');
Route::get('/pages/maceraci-info', [AdventurerInfoController::class, 'index'])->name('valiant-shade.pages-maceraci-info');
Route::get('/pages/guild-overview', [AdventurerInfoController::class, 'index'])->name('valiant-shade.pages-guild-overview');

Route::post('/pages/adventurer-info/allowance', [AdventurerInfoController::class, 'updateAllowance'])->name('valiant-shade.pages-adventurer-info.allowance');
Route::post('/pages/maceraci-info/allowance', [AdventurerInfoController::class, 'updateAllowance'])->name('valiant-shade.pages-maceraci-info.allowance');

Route::put('/pages/adventurer-info/bailiff/{id}', [AdventurerInfoController::class, 'updateBailiff'])->name('valiant-shade.pages-adventurer-info.bailiff');
Route::put('/pages/maceraci-info/bailiff/{id}', [AdventurerInfoController::class, 'updateBailiff'])->name('valiant-shade.pages-maceraci-info.bailiff');

Route::post('/pages/adventurer-info/guild-level', [AdventurerInfoController::class, 'updateGuildLevel'])->name('valiant-shade.pages-adventurer-info.guild-level');
Route::post('/pages/maceraci-info/guild-level', [AdventurerInfoController::class, 'updateGuildLevel'])->name('valiant-shade.pages-maceraci-info.guild-level');

Route::post('/pages/adventurer-info/reset-excel', [AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-adventurer-info.reset-excel');
Route::post('/pages/adventurer-info/reset-defaults', [AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-adventurer-info.reset-defaults');
Route::post('/pages/maceraci-info/reset-excel', [AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-maceraci-info.reset-excel');
Route::post('/pages/maceraci-info/reset-defaults', [AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-maceraci-info.reset-defaults');

// Medieval Quest Board (Lonca Görev Panosu) Routes
Route::get('/pages/quest-board', [QuestBoardController::class, 'index'])->name('valiant-shade.pages-quest-board');
Route::get('/pages/gorev-listesi', [QuestBoardController::class, 'index'])->name('valiant-shade.pages-gorev-listesi');
Route::middleware(['throttle:valiant-shade.quest-mutations'])->group(function () {
    Route::post('/pages/quest-board', [QuestBoardController::class, 'store'])->name('valiant-shade.pages-quest-board.store');
    Route::put('/pages/quest-board/{id}', [QuestBoardController::class, 'update'])->name('valiant-shade.pages-quest-board.update');
    Route::delete('/pages/quest-board/{id}', [QuestBoardController::class, 'destroy'])->name('valiant-shade.pages-quest-board.destroy');
    Route::post('/pages/quest-board/{id}/status', [QuestBoardController::class, 'updateStatus'])->name('valiant-shade.pages-quest-board.status');
    Route::post('/pages/quest-board/{id}/broadcast', [QuestBoardController::class, 'broadcastQuest'])->name('valiant-shade.pages-quest-board.broadcast');
    Route::post('/pages/quest-board/reset-defaults', [QuestBoardController::class, 'resetDefaults'])->name('valiant-shade.pages-quest-board.reset-defaults');
    Route::post('/pages/quest-board/discord-settings', [QuestBoardController::class, 'saveDiscordSettings'])->name('valiant-shade.pages-quest-board.discord-settings');
    Route::post('/pages/quest-board/discord-test', [QuestBoardController::class, 'testDiscordWebhook'])->name('valiant-shade.pages-quest-board.discord-test');
});

// Quest Reports (Görev Raporları) Routes
Route::get('/pages/quest-reports', [QuestReportsController::class, 'index'])->name('valiant-shade.pages-quest-reports');
Route::get('/pages/gorev-raporlari', [QuestReportsController::class, 'index'])->name('valiant-shade.pages-gorev-raporlari');
Route::middleware(LockQuestReportMutations::class)->group(function () {
    Route::post('/pages/quest-reports/import', [QuestReportsController::class, 'import'])
        ->middleware(EnsureUserIsAdmin::class)
        ->name('valiant-shade.pages-quest-reports.import');
    Route::post('/pages/quest-reports/guild-pool/import', [QuestReportsController::class, 'importGuildPool'])
        ->middleware(EnsureUserIsAdmin::class)
        ->name('valiant-shade.pages-quest-reports.guild-pool.import');
    Route::post('/pages/quest-reports', [QuestReportsController::class, 'store'])->name('valiant-shade.pages-quest-reports.store');
    Route::delete('/pages/quest-reports', [QuestReportsController::class, 'clear'])
        ->middleware(EnsureUserIsAdmin::class)
        ->name('valiant-shade.pages-quest-reports.clear');
    Route::put('/pages/quest-reports/{id}', [QuestReportsController::class, 'update'])->name('valiant-shade.pages-quest-reports.update');
    Route::delete('/pages/quest-reports/{id}', [QuestReportsController::class, 'destroy'])->name('valiant-shade.pages-quest-reports.destroy');
    Route::post('/pages/quest-reports/{id}/distribute-xp', [QuestReportsController::class, 'distributeXp'])->name('valiant-shade.pages-quest-reports.distribute-xp');
    Route::post('/pages/quest-reports/reset-defaults', [QuestReportsController::class, 'resetDefaults'])->name('valiant-shade.pages-quest-reports.reset-defaults');
    Route::post('/pages/quest-reports/reset-excel', [QuestReportsController::class, 'resetExcel'])->name('valiant-shade.pages-quest-reports.reset-excel');
});
Route::get('/pages/quest-reports/{id}', [QuestReportsController::class, 'show'])->name('valiant-shade.pages-quest-reports.show');
Route::get('/pages/quest-reports-pool', [QuestReportsController::class, 'getGuildPool'])->name('valiant-shade.pages-quest-reports.guild-pool.get');
Route::post('/pages/quest-reports-pool/update', [QuestReportsController::class, 'updateGuildPool'])->name('valiant-shade.pages-quest-reports.guild-pool.update');
Route::post('/pages/quest-reports-pool/reset', [QuestReportsController::class, 'resetGuildPool'])->name('valiant-shade.pages-quest-reports.guild-pool.reset');
Route::post('/pages/quest-reports/guild-pool/update', [QuestReportsController::class, 'updateGuildPool']);
Route::post('/pages/quest-reports/guild-pool/reset', [QuestReportsController::class, 'resetGuildPool']);

// Pathfinder 1E Feat and Traits & Character Progression Routes
Route::get('/pages/featntrait', [FeatsListController::class, 'index'])->name('valiant-shade.pages-featntrait');
Route::get('/pages/feats', function (Request $request) {
    $qs = $request->getQueryString();

    return redirect()->to('/valiant-shade/pages/featntrait'.($qs ? '?'.$qs : ''), 301);
})->name('valiant-shade.pages-feats-list');
Route::get('/pages/feats-list', function (Request $request) {
    $qs = $request->getQueryString();

    return redirect()->to('/valiant-shade/pages/featntrait'.($qs ? '?'.$qs : ''), 301);
});
Route::get('/pages/feats-listesi', function (Request $request) {
    $qs = $request->getQueryString();

    return redirect()->to('/valiant-shade/pages/featntrait'.($qs ? '?'.$qs : ''), 301);
});
Route::get('/pages/feat-listesi', function (Request $request) {
    $qs = $request->getQueryString();

    return redirect()->to('/valiant-shade/pages/featntrait'.($qs ? '?'.$qs : ''), 301);
});
Route::get('/pages/feats-and-traits', function (Request $request) {
    $qs = $request->getQueryString();

    return redirect()->to('/valiant-shade/pages/featntrait'.($qs ? '?'.$qs : ''), 301);
})->name('valiant-shade.pages-feats-and-traits');
Route::get('/pages/feat-and-traits', function (Request $request) {
    $qs = $request->getQueryString();

    return redirect()->to('/valiant-shade/pages/featntrait'.($qs ? '?'.$qs : ''), 301);
})->name('valiant-shade.pages-feat-and-traits');

// Feat & Trait Action Routes (/pages/featntrait)
Route::post('/pages/featntrait', [FeatsListController::class, 'store'])->name('valiant-shade.pages-featntrait.store');
Route::put('/pages/featntrait/{id}', [FeatsListController::class, 'update'])->name('valiant-shade.pages-featntrait.update');
Route::delete('/pages/featntrait/{id}', [FeatsListController::class, 'destroy'])->name('valiant-shade.pages-featntrait.destroy');
Route::delete('/pages/featntrait/character/delete', [FeatsListController::class, 'deleteCharacter'])->name('valiant-shade.pages-featntrait.character.destroy');
Route::post('/pages/featntrait/stats', [FeatsListController::class, 'updateStats'])->name('valiant-shade.pages-featntrait.stats');
Route::post('/pages/featntrait/reset-excel', [FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-featntrait.reset-excel');
Route::post('/pages/featntrait/reset-defaults', [FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-featntrait.reset-defaults');

// Legacy Aliases (/pages/feats)
Route::post('/pages/feats', [FeatsListController::class, 'store'])->name('valiant-shade.pages-feats.store');
Route::put('/pages/feats/{id}', [FeatsListController::class, 'update'])->name('valiant-shade.pages-feats.update');
Route::delete('/pages/feats/{id}', [FeatsListController::class, 'destroy'])->name('valiant-shade.pages-feats.destroy');
Route::delete('/pages/feats/character/delete', [FeatsListController::class, 'deleteCharacter'])->name('valiant-shade.pages-feats.character.destroy');
Route::post('/pages/feats/stats', [FeatsListController::class, 'updateStats'])->name('valiant-shade.pages-feats.stats');
Route::post('/pages/feats/reset-excel', [FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-feats.reset-excel');
Route::post('/pages/feats/reset-defaults', [FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-feats.reset-defaults');

// Character Traits Routes (Guild Rank Progression: Üye, Kıdemli, Uzman)
Route::post('/pages/traits/save', [FeatsListController::class, 'saveTrait'])->name('valiant-shade.pages-traits.save');
Route::post('/pages/traits/clear', [FeatsListController::class, 'clearTrait'])->name('valiant-shade.pages-traits.clear');
Route::get('/pages/traits/search', [FeatsListController::class, 'searchTraits'])->name('valiant-shade.pages-traits.search');
Route::post('/pages/featntrait/traits/save', [FeatsListController::class, 'saveTrait'])->name('valiant-shade.pages-featntrait.traits.save');
Route::post('/pages/featntrait/traits/clear', [FeatsListController::class, 'clearTrait'])->name('valiant-shade.pages-featntrait.traits.clear');
Route::get('/pages/featntrait/traits/search', [FeatsListController::class, 'searchTraits'])->name('valiant-shade.pages-featntrait.traits.search');

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
Route::get('/app/user/{id}/access', [UserList::class, 'access'])->name('valiant-shade.app-user-list.access');
// Roles & Permissions Management (Strictly Admin Only)
Route::middleware([EnsureUserIsAdmin::class])->group(function () {
    Route::get('/app/health', [HealthController::class, 'index'])->name('valiant-shade.app-health');
    Route::get('/app/audit-logs', [AuditLogController::class, 'index'])->name('valiant-shade.app-audit-logs');
    Route::middleware(['throttle:valiant-shade.admin-mutations'])->group(function () {
        Route::delete('/app/audit-logs/clear', [AuditLogController::class, 'clear'])->name('valiant-shade.app-audit-logs.clear');
    });
});

Route::middleware([EnsureCanManageRoles::class.':read'])->group(function () {
    Route::get('/app/roles', [RoleController::class, 'index'])->name('valiant-shade.app-roles.index');
    Route::get('/app/roles-permissions', [RoleController::class, 'index'])->name('valiant-shade.app-roles-permissions');
});

Route::middleware([EnsureCanManageRoles::class.':write', 'throttle:valiant-shade.admin-mutations'])->group(function () {
    Route::post('/app/roles', [RoleController::class, 'store'])->name('valiant-shade.app-roles.store');
    Route::put('/app/roles/{role}', [RoleController::class, 'update'])->name('valiant-shade.app-roles.update');
    Route::delete('/app/roles/{role}', [RoleController::class, 'destroy'])->name('valiant-shade.app-roles.destroy');
    Route::post('/app/roles/{role}/toggle-permission', [RoleController::class, 'togglePermission'])->name('valiant-shade.app-roles.toggle-permission');
});
Route::match(['GET', 'POST'], '/logout', [LogoutController::class, 'logout'])->name('logout');
Route::match(['GET', 'POST'], '/auth/logout', [LogoutController::class, 'logout'])->name('valiant-shade.auth-logout');
