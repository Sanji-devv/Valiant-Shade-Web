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
Route::post('/character/delete/{id}', [UserProfile::class, 'deleteCharacter'])->name('valiant-shade.character.delete');
Route::delete('/character/{id}', [UserProfile::class, 'deleteCharacter'])->name('valiant-shade.character.destroy');
Route::post('/character/set-active', [UserProfile::class, 'setActiveCharacter'])->name('valiant-shade.character.set-active');
Route::get('/pages/inventory', [Inventory::class, 'index'])->name('valiant-shade.pages-inventory');
Route::post('/pages/inventory', [Inventory::class, 'store'])->name('valiant-shade.pages-inventory.store');
Route::put('/pages/inventory/{id}', [Inventory::class, 'update'])->name('valiant-shade.pages-inventory.update');
Route::delete('/pages/inventory/{id}', [Inventory::class, 'destroy'])->name('valiant-shade.pages-inventory.destroy');
Route::post('/pages/inventory/reset-excel', [Inventory::class, 'resetExcel'])->name('valiant-shade.pages-inventory.reset-excel');
Route::post('/pages/inventory/vault', [Inventory::class, 'updateVault'])->name('valiant-shade.pages-inventory.vault');

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
Route::post('/pages/maceraci-info/reset-excel', [\App\ValiantShade\Http\Controllers\pages\AdventurerInfoController::class, 'resetExcel'])->name('valiant-shade.pages-maceraci-info.reset-excel');

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

// Pathfinder 1E Feats List & Character Progression Routes
Route::get('/pages/feats', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'index'])->name('valiant-shade.pages-feats-list');
Route::get('/pages/feats-list', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'index']);
Route::get('/pages/feat-listesi', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'index']);
Route::post('/pages/feats', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'store'])->name('valiant-shade.pages-feats.store');
Route::put('/pages/feats/{id}', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'update'])->name('valiant-shade.pages-feats.update');
Route::delete('/pages/feats/{id}', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'destroy'])->name('valiant-shade.pages-feats.destroy');
Route::delete('/pages/feats/character/delete', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'deleteCharacter'])->name('valiant-shade.pages-feats.character.destroy');
Route::post('/pages/feats/stats', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'updateStats'])->name('valiant-shade.pages-feats.stats');
Route::post('/pages/feats/reset-excel', [\App\ValiantShade\Http\Controllers\pages\FeatsListController::class, 'resetExcel'])->name('valiant-shade.pages-feats.reset-excel');

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
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
Route::post('/auth/logout', [LogoutController::class, 'logout'])->name('valiant-shade.auth-logout');


