<?php

use Illuminate\Support\Facades\Route;
use DemoVuexy\Http\Controllers\laravel_example\UserManagement;
use DemoVuexy\Http\Controllers\dashboard\Analytics;
use DemoVuexy\Http\Controllers\dashboard\Crm;
use DemoVuexy\Http\Controllers\language\LanguageController;
use DemoVuexy\Http\Controllers\layouts\CollapsedMenu;
use DemoVuexy\Http\Controllers\layouts\ContentNavbar;
use DemoVuexy\Http\Controllers\layouts\ContentNavSidebar;
use DemoVuexy\Http\Controllers\layouts\Horizontal;
use DemoVuexy\Http\Controllers\layouts\Vertical;
use DemoVuexy\Http\Controllers\layouts\WithoutMenu;
use DemoVuexy\Http\Controllers\layouts\WithoutNavbar;
use DemoVuexy\Http\Controllers\layouts\Fluid;
use DemoVuexy\Http\Controllers\layouts\Container;
use DemoVuexy\Http\Controllers\layouts\Blank;
use DemoVuexy\Http\Controllers\front_pages\Landing;
use DemoVuexy\Http\Controllers\front_pages\Pricing;
use DemoVuexy\Http\Controllers\front_pages\Payment;
use DemoVuexy\Http\Controllers\front_pages\Checkout;
use DemoVuexy\Http\Controllers\front_pages\HelpCenter;
use DemoVuexy\Http\Controllers\front_pages\HelpCenterArticle;
use DemoVuexy\Http\Controllers\apps\Email;
use DemoVuexy\Http\Controllers\apps\Chat;
use DemoVuexy\Http\Controllers\apps\Calendar;
use DemoVuexy\Http\Controllers\apps\Kanban;
use DemoVuexy\Http\Controllers\apps\EcommerceDashboard;
use DemoVuexy\Http\Controllers\apps\EcommerceProductList;
use DemoVuexy\Http\Controllers\apps\EcommerceProductAdd;
use DemoVuexy\Http\Controllers\apps\EcommerceProductCategory;
use DemoVuexy\Http\Controllers\apps\EcommerceOrderList;
use DemoVuexy\Http\Controllers\apps\EcommerceOrderDetails;
use DemoVuexy\Http\Controllers\apps\EcommerceCustomerAll;
use DemoVuexy\Http\Controllers\apps\EcommerceCustomerDetailsOverview;
use DemoVuexy\Http\Controllers\apps\EcommerceCustomerDetailsSecurity;
use DemoVuexy\Http\Controllers\apps\EcommerceCustomerDetailsBilling;
use DemoVuexy\Http\Controllers\apps\EcommerceCustomerDetailsNotifications;
use DemoVuexy\Http\Controllers\apps\EcommerceManageReviews;
use DemoVuexy\Http\Controllers\apps\EcommerceReferrals;
use DemoVuexy\Http\Controllers\apps\EcommerceSettingsDetails;
use DemoVuexy\Http\Controllers\apps\EcommerceSettingsPayments;
use DemoVuexy\Http\Controllers\apps\EcommerceSettingsCheckout;
use DemoVuexy\Http\Controllers\apps\EcommerceSettingsShipping;
use DemoVuexy\Http\Controllers\apps\EcommerceSettingsLocations;
use DemoVuexy\Http\Controllers\apps\EcommerceSettingsNotifications;
use DemoVuexy\Http\Controllers\apps\AcademyDashboard;
use DemoVuexy\Http\Controllers\apps\AcademyCourse;
use DemoVuexy\Http\Controllers\apps\AcademyCourseDetails;
use DemoVuexy\Http\Controllers\apps\LogisticsDashboard;
use DemoVuexy\Http\Controllers\apps\LogisticsFleet;
use DemoVuexy\Http\Controllers\apps\InvoiceList;
use DemoVuexy\Http\Controllers\apps\InvoicePreview;
use DemoVuexy\Http\Controllers\apps\InvoicePrint;
use DemoVuexy\Http\Controllers\apps\InvoiceEdit;
use DemoVuexy\Http\Controllers\apps\InvoiceAdd;
use DemoVuexy\Http\Controllers\apps\UserList;
use DemoVuexy\Http\Controllers\apps\UserViewAccount;
use DemoVuexy\Http\Controllers\apps\UserViewSecurity;
use DemoVuexy\Http\Controllers\apps\UserViewBilling;
use DemoVuexy\Http\Controllers\apps\UserViewNotifications;
use DemoVuexy\Http\Controllers\apps\UserViewConnections;
use DemoVuexy\Http\Controllers\apps\AccessRoles;
use DemoVuexy\Http\Controllers\apps\AccessPermission;
use DemoVuexy\Http\Controllers\pages\UserProfile;
use DemoVuexy\Http\Controllers\pages\UserTeams;
use DemoVuexy\Http\Controllers\pages\UserProjects;
use DemoVuexy\Http\Controllers\pages\UserConnections;
use DemoVuexy\Http\Controllers\pages\AccountSettingsAccount;
use DemoVuexy\Http\Controllers\pages\AccountSettingsSecurity;
use DemoVuexy\Http\Controllers\pages\AccountSettingsBilling;
use DemoVuexy\Http\Controllers\pages\AccountSettingsNotifications;
use DemoVuexy\Http\Controllers\pages\AccountSettingsConnections;
use DemoVuexy\Http\Controllers\pages\Faq;
use DemoVuexy\Http\Controllers\pages\Pricing as PagesPricing;
use DemoVuexy\Http\Controllers\pages\MiscError;
use DemoVuexy\Http\Controllers\pages\MiscUnderMaintenance;
use DemoVuexy\Http\Controllers\pages\MiscComingSoon;
use DemoVuexy\Http\Controllers\pages\MiscNotAuthorized;
use DemoVuexy\Http\Controllers\authentications\LoginBasic;
use DemoVuexy\Http\Controllers\authentications\LoginCover;
use DemoVuexy\Http\Controllers\authentications\RegisterBasic;
use DemoVuexy\Http\Controllers\authentications\RegisterCover;
use DemoVuexy\Http\Controllers\authentications\RegisterMultiSteps;
use DemoVuexy\Http\Controllers\authentications\VerifyEmailBasic;
use DemoVuexy\Http\Controllers\authentications\VerifyEmailCover;
use DemoVuexy\Http\Controllers\authentications\ResetPasswordBasic;
use DemoVuexy\Http\Controllers\authentications\ResetPasswordCover;
use DemoVuexy\Http\Controllers\authentications\ForgotPasswordBasic;
use DemoVuexy\Http\Controllers\authentications\ForgotPasswordCover;
use DemoVuexy\Http\Controllers\authentications\TwoStepsBasic;
use DemoVuexy\Http\Controllers\authentications\TwoStepsCover;
use DemoVuexy\Http\Controllers\wizard_example\Checkout as WizardCheckout;
use DemoVuexy\Http\Controllers\wizard_example\PropertyListing;
use DemoVuexy\Http\Controllers\wizard_example\CreateDeal;
use DemoVuexy\Http\Controllers\modal\ModalExample;
use DemoVuexy\Http\Controllers\cards\CardBasic;
use DemoVuexy\Http\Controllers\cards\CardAdvance;
use DemoVuexy\Http\Controllers\cards\CardStatistics;
use DemoVuexy\Http\Controllers\cards\CardAnalytics;
use DemoVuexy\Http\Controllers\cards\CardActions;
use DemoVuexy\Http\Controllers\user_interface\Accordion;
use DemoVuexy\Http\Controllers\user_interface\Alerts;
use DemoVuexy\Http\Controllers\user_interface\Badges;
use DemoVuexy\Http\Controllers\user_interface\Buttons;
use DemoVuexy\Http\Controllers\user_interface\Carousel;
use DemoVuexy\Http\Controllers\user_interface\Collapse;
use DemoVuexy\Http\Controllers\user_interface\Dropdowns;
use DemoVuexy\Http\Controllers\user_interface\Footer;
use DemoVuexy\Http\Controllers\user_interface\ListGroups;
use DemoVuexy\Http\Controllers\user_interface\Modals;
use DemoVuexy\Http\Controllers\user_interface\Navbar;
use DemoVuexy\Http\Controllers\user_interface\Offcanvas;
use DemoVuexy\Http\Controllers\user_interface\PaginationBreadcrumbs;
use DemoVuexy\Http\Controllers\user_interface\Progress;
use DemoVuexy\Http\Controllers\user_interface\Spinners;
use DemoVuexy\Http\Controllers\user_interface\TabsPills;
use DemoVuexy\Http\Controllers\user_interface\Toasts;
use DemoVuexy\Http\Controllers\user_interface\TooltipsPopovers;
use DemoVuexy\Http\Controllers\user_interface\Typography;
use DemoVuexy\Http\Controllers\extended_ui\Avatar;
use DemoVuexy\Http\Controllers\extended_ui\BlockUI;
use DemoVuexy\Http\Controllers\extended_ui\DragAndDrop;
use DemoVuexy\Http\Controllers\extended_ui\MediaPlayer;
use DemoVuexy\Http\Controllers\extended_ui\PerfectScrollbar;
use DemoVuexy\Http\Controllers\extended_ui\StarRatings;
use DemoVuexy\Http\Controllers\extended_ui\SweetAlert;
use DemoVuexy\Http\Controllers\extended_ui\TextDivider;
use DemoVuexy\Http\Controllers\extended_ui\TimelineBasic;
use DemoVuexy\Http\Controllers\extended_ui\TimelineFullscreen;
use DemoVuexy\Http\Controllers\extended_ui\Tour;
use DemoVuexy\Http\Controllers\extended_ui\Treeview;
use DemoVuexy\Http\Controllers\extended_ui\Misc;
use DemoVuexy\Http\Controllers\icons\Tabler;
use DemoVuexy\Http\Controllers\icons\FontAwesome;
use DemoVuexy\Http\Controllers\form_elements\BasicInput;
use DemoVuexy\Http\Controllers\form_elements\InputGroups;
use DemoVuexy\Http\Controllers\form_elements\CustomOptions;
use DemoVuexy\Http\Controllers\form_elements\Editors;
use DemoVuexy\Http\Controllers\form_elements\FileUpload;
use DemoVuexy\Http\Controllers\form_elements\Picker;
use DemoVuexy\Http\Controllers\form_elements\Selects;
use DemoVuexy\Http\Controllers\form_elements\Sliders;
use DemoVuexy\Http\Controllers\form_elements\Switches;
use DemoVuexy\Http\Controllers\form_elements\Extras;
use DemoVuexy\Http\Controllers\form_layouts\VerticalForm;
use DemoVuexy\Http\Controllers\form_layouts\HorizontalForm;
use DemoVuexy\Http\Controllers\form_layouts\StickyActions;
use DemoVuexy\Http\Controllers\form_wizard\Numbered as FormWizardNumbered;
use DemoVuexy\Http\Controllers\form_wizard\Icons as FormWizardIcons;
use DemoVuexy\Http\Controllers\form_validation\Validation;
use DemoVuexy\Http\Controllers\tables\Basic as TablesBasic;
use DemoVuexy\Http\Controllers\tables\DatatableBasic;
use DemoVuexy\Http\Controllers\tables\DatatableAdvanced;
use DemoVuexy\Http\Controllers\tables\DatatableExtensions;
use DemoVuexy\Http\Controllers\charts\ApexCharts;
use DemoVuexy\Http\Controllers\charts\ChartJs;
use DemoVuexy\Http\Controllers\maps\Leaflet;

// Main Page Route
Route::get('/', [Analytics::class, 'index'])->name('demo-vuexy.dashboard-analytics');
Route::get('/dashboard/analytics', [Analytics::class, 'index']);
Route::get('/dashboard/crm', [Crm::class, 'index'])->name('demo-vuexy.dashboard-crm');
// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);

// layout
Route::get('/layouts/collapsed-menu', [CollapsedMenu::class, 'index'])->name('demo-vuexy.layouts-collapsed-menu');
Route::get('/layouts/content-navbar', [ContentNavbar::class, 'index'])->name('demo-vuexy.layouts-content-navbar');
Route::get('/layouts/content-nav-sidebar', [ContentNavSidebar::class, 'index'])->name('demo-vuexy.layouts-content-nav-sidebar');
Route::get('/layouts/horizontal', [Horizontal::class, 'index'])->name('demo-vuexy.layouts-horizontal');
Route::get('/layouts/vertical', [Vertical::class, 'index'])->name('demo-vuexy.layouts-vertical');
Route::get('/layouts/without-menu', [WithoutMenu::class, 'index'])->name('demo-vuexy.layouts-without-menu');
Route::get('/layouts/without-navbar', [WithoutNavbar::class, 'index'])->name('demo-vuexy.layouts-without-navbar');
Route::get('/layouts/fluid', [Fluid::class, 'index'])->name('demo-vuexy.layouts-fluid');
Route::get('/layouts/container', [Container::class, 'index'])->name('demo-vuexy.layouts-container');
Route::get('/layouts/blank', [Blank::class, 'index'])->name('demo-vuexy.layouts-blank');

// Front Pages
Route::get('/front-pages/landing', [Landing::class, 'index'])->name('demo-vuexy.front-pages-landing');
Route::get('/front-pages/pricing', [Pricing::class, 'index'])->name('demo-vuexy.front-pages-pricing');
Route::get('/front-pages/payment', [Payment::class, 'index'])->name('demo-vuexy.front-pages-payment');
Route::get('/front-pages/checkout', [Checkout::class, 'index'])->name('demo-vuexy.front-pages-checkout');
Route::get('/front-pages/help-center', [HelpCenter::class, 'index'])->name('demo-vuexy.front-pages-help-center');
Route::get('/front-pages/help-center-article', [HelpCenterArticle::class, 'index'])->name('demo-vuexy.front-pages-help-center-article');

// apps
Route::get('/app/email', [Email::class, 'index'])->name('demo-vuexy.app-email');
Route::get('/app/chat', [Chat::class, 'index'])->name('demo-vuexy.app-chat');
Route::get('/app/calendar', [Calendar::class, 'index'])->name('demo-vuexy.app-calendar');
Route::get('/app/kanban', [Kanban::class, 'index'])->name('demo-vuexy.app-kanban');
Route::get('/app/ecommerce/dashboard', [EcommerceDashboard::class, 'index'])->name('demo-vuexy.app-ecommerce-dashboard');
Route::get('/app/ecommerce/product/list', [EcommerceProductList::class, 'index'])->name('demo-vuexy.app-ecommerce-product-list');
Route::get('/app/ecommerce/product/add', [EcommerceProductAdd::class, 'index'])->name('demo-vuexy.app-ecommerce-product-add');
Route::get('/app/ecommerce/product/category', [EcommerceProductCategory::class, 'index'])->name('demo-vuexy.app-ecommerce-product-category');
Route::get('/app/ecommerce/order/list', [EcommerceOrderList::class, 'index'])->name('demo-vuexy.app-ecommerce-order-list');
Route::get('/app/ecommerce/order/details', [EcommerceOrderDetails::class, 'index'])->name('demo-vuexy.app-ecommerce-order-details');
Route::get('/app/ecommerce/customer/all', [EcommerceCustomerAll::class, 'index'])->name('demo-vuexy.app-ecommerce-customer-all');
Route::get('/app/ecommerce/customer/details/overview', [EcommerceCustomerDetailsOverview::class, 'index'])->name('demo-vuexy.app-ecommerce-customer-details-overview');
Route::get('/app/ecommerce/customer/details/security', [EcommerceCustomerDetailsSecurity::class, 'index'])->name('demo-vuexy.app-ecommerce-customer-details-security');
Route::get('/app/ecommerce/customer/details/billing', [EcommerceCustomerDetailsBilling::class, 'index'])->name('demo-vuexy.app-ecommerce-customer-details-billing');
Route::get('/app/ecommerce/customer/details/notifications', [EcommerceCustomerDetailsNotifications::class, 'index'])->name('demo-vuexy.app-ecommerce-customer-details-notifications');
Route::get('/app/ecommerce/manage/reviews', [EcommerceManageReviews::class, 'index'])->name('demo-vuexy.app-ecommerce-manage-reviews');
Route::get('/app/ecommerce/referrals', [EcommerceReferrals::class, 'index'])->name('demo-vuexy.app-ecommerce-referrals');
Route::get('/app/ecommerce/settings/details', [EcommerceSettingsDetails::class, 'index'])->name('demo-vuexy.app-ecommerce-settings-details');
Route::get('/app/ecommerce/settings/payments', [EcommerceSettingsPayments::class, 'index'])->name('demo-vuexy.app-ecommerce-settings-payments');
Route::get('/app/ecommerce/settings/checkout', [EcommerceSettingsCheckout::class, 'index'])->name('demo-vuexy.app-ecommerce-settings-checkout');
Route::get('/app/ecommerce/settings/shipping', [EcommerceSettingsShipping::class, 'index'])->name('demo-vuexy.app-ecommerce-settings-shipping');
Route::get('/app/ecommerce/settings/locations', [EcommerceSettingsLocations::class, 'index'])->name('demo-vuexy.app-ecommerce-settings-locations');
Route::get('/app/ecommerce/settings/notifications', [EcommerceSettingsNotifications::class, 'index'])->name('demo-vuexy.app-ecommerce-settings-notifications');
Route::get('/app/academy/dashboard', [AcademyDashboard::class, 'index'])->name('demo-vuexy.app-academy-dashboard');
Route::get('/app/academy/course', [AcademyCourse::class, 'index'])->name('demo-vuexy.app-academy-course');
Route::get('/app/academy/course-details', [AcademyCourseDetails::class, 'index'])->name('demo-vuexy.app-academy-course-details');
Route::get('/app/logistics/dashboard', [LogisticsDashboard::class, 'index'])->name('demo-vuexy.app-logistics-dashboard');
Route::get('/app/logistics/fleet', [LogisticsFleet::class, 'index'])->name('demo-vuexy.app-logistics-fleet');
Route::get('/app/invoice/list', [InvoiceList::class, 'index'])->name('demo-vuexy.app-invoice-list');
Route::get('/app/invoice/preview', [InvoicePreview::class, 'index'])->name('demo-vuexy.app-invoice-preview');
Route::get('/app/invoice/print', [InvoicePrint::class, 'index'])->name('demo-vuexy.app-invoice-print');
Route::get('/app/invoice/edit', [InvoiceEdit::class, 'index'])->name('demo-vuexy.app-invoice-edit');
Route::get('/app/invoice/add', [InvoiceAdd::class, 'index'])->name('demo-vuexy.app-invoice-add');
Route::get('/app/user/list', [UserList::class, 'index'])->name('demo-vuexy.app-user-list');
Route::get('/app/user/view/account', [UserViewAccount::class, 'index'])->name('demo-vuexy.app-user-view-account');
Route::get('/app/user/view/security', [UserViewSecurity::class, 'index'])->name('demo-vuexy.app-user-view-security');
Route::get('/app/user/view/billing', [UserViewBilling::class, 'index'])->name('demo-vuexy.app-user-view-billing');
Route::get('/app/user/view/notifications', [UserViewNotifications::class, 'index'])->name('demo-vuexy.app-user-view-notifications');
Route::get('/app/user/view/connections', [UserViewConnections::class, 'index'])->name('demo-vuexy.app-user-view-connections');
Route::get('/app/access-roles', [AccessRoles::class, 'index'])->name('demo-vuexy.app-access-roles');
Route::get('/app/access-permission', [AccessPermission::class, 'index'])->name('demo-vuexy.app-access-permission');

// pages
Route::get('/pages/profile-user', [UserProfile::class, 'index'])->name('demo-vuexy.pages-profile-user');
Route::get('/pages/profile-teams', [UserTeams::class, 'index'])->name('demo-vuexy.pages-profile-teams');
Route::get('/pages/profile-projects', [UserProjects::class, 'index'])->name('demo-vuexy.pages-profile-projects');
Route::get('/pages/profile-connections', [UserConnections::class, 'index'])->name('demo-vuexy.pages-profile-connections');
Route::get('/pages/account-settings-account', [AccountSettingsAccount::class, 'index'])->name('demo-vuexy.pages-account-settings-account');
Route::get('/pages/account-settings-security', [AccountSettingsSecurity::class, 'index'])->name('demo-vuexy.pages-account-settings-security');
Route::get('/pages/account-settings-billing', [AccountSettingsBilling::class, 'index'])->name('demo-vuexy.pages-account-settings-billing');
Route::get('/pages/account-settings-notifications', [AccountSettingsNotifications::class, 'index'])->name('demo-vuexy.pages-account-settings-notifications');
Route::get('/pages/account-settings-connections', [AccountSettingsConnections::class, 'index'])->name('demo-vuexy.pages-account-settings-connections');
Route::get('/pages/faq', [Faq::class, 'index'])->name('demo-vuexy.pages-faq');
Route::get('/pages/pricing', [PagesPricing::class, 'index'])->name('demo-vuexy.pages-pricing');
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('demo-vuexy.pages-misc-error');
Route::get('/pages/misc-under-maintenance', [MiscUnderMaintenance::class, 'index'])->name('demo-vuexy.pages-misc-under-maintenance');
Route::get('/pages/misc-comingsoon', [MiscComingSoon::class, 'index'])->name('demo-vuexy.pages-misc-comingsoon');
Route::get('/pages/misc-not-authorized', [MiscNotAuthorized::class, 'index'])->name('demo-vuexy.pages-misc-not-authorized');

// authentication
Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('demo-vuexy.auth-login-basic');
Route::get('/auth/login-cover', [LoginCover::class, 'index'])->name('demo-vuexy.auth-login-cover');
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('demo-vuexy.auth-register-basic');
Route::get('/auth/register-cover', [RegisterCover::class, 'index'])->name('demo-vuexy.auth-register-cover');
Route::get('/auth/register-multisteps', [RegisterMultiSteps::class, 'index'])->name('demo-vuexy.auth-register-multisteps');
Route::get('/auth/verify-email-basic', [VerifyEmailBasic::class, 'index'])->name('demo-vuexy.auth-verify-email-basic');
Route::get('/auth/verify-email-cover', [VerifyEmailCover::class, 'index'])->name('demo-vuexy.auth-verify-email-cover');
Route::get('/auth/reset-password-basic', [ResetPasswordBasic::class, 'index'])->name('demo-vuexy.auth-reset-password-basic');
Route::get('/auth/reset-password-cover', [ResetPasswordCover::class, 'index'])->name('demo-vuexy.auth-reset-password-cover');
Route::get('/auth/forgot-password-basic', [ForgotPasswordBasic::class, 'index'])->name('demo-vuexy.auth-forgot-password-basic');
Route::get('/auth/forgot-password-cover', [ForgotPasswordCover::class, 'index'])->name('demo-vuexy.auth-forgot-password-cover');
Route::get('/auth/two-steps-basic', [TwoStepsBasic::class, 'index'])->name('demo-vuexy.auth-two-steps-basic');
Route::get('/auth/two-steps-cover', [TwoStepsCover::class, 'index'])->name('demo-vuexy.auth-two-steps-cover');

// wizard example
Route::get('/wizard/ex-checkout', [WizardCheckout::class, 'index'])->name('demo-vuexy.wizard-ex-checkout');
Route::get('/wizard/ex-property-listing', [PropertyListing::class, 'index'])->name('demo-vuexy.wizard-ex-property-listing');
Route::get('/wizard/ex-create-deal', [CreateDeal::class, 'index'])->name('demo-vuexy.wizard-ex-create-deal');

// modal
Route::get('/modal-examples', [ModalExample::class, 'index'])->name('demo-vuexy.modal-examples');

// cards
Route::get('/cards/basic', [CardBasic::class, 'index'])->name('demo-vuexy.cards-basic');
Route::get('/cards/advance', [CardAdvance::class, 'index'])->name('demo-vuexy.cards-advance');
Route::get('/cards/statistics', [CardStatistics::class, 'index'])->name('demo-vuexy.cards-statistics');
Route::get('/cards/analytics', [CardAnalytics::class, 'index'])->name('demo-vuexy.cards-analytics');
Route::get('/cards/actions', [CardActions::class, 'index'])->name('demo-vuexy.cards-actions');

// User Interface
Route::get('/ui/accordion', [Accordion::class, 'index'])->name('demo-vuexy.ui-accordion');
Route::get('/ui/alerts', [Alerts::class, 'index'])->name('demo-vuexy.ui-alerts');
Route::get('/ui/badges', [Badges::class, 'index'])->name('demo-vuexy.ui-badges');
Route::get('/ui/buttons', [Buttons::class, 'index'])->name('demo-vuexy.ui-buttons');
Route::get('/ui/carousel', [Carousel::class, 'index'])->name('demo-vuexy.ui-carousel');
Route::get('/ui/collapse', [Collapse::class, 'index'])->name('demo-vuexy.ui-collapse');
Route::get('/ui/dropdowns', [Dropdowns::class, 'index'])->name('demo-vuexy.ui-dropdowns');
Route::get('/ui/footer', [Footer::class, 'index'])->name('demo-vuexy.ui-footer');
Route::get('/ui/list-groups', [ListGroups::class, 'index'])->name('demo-vuexy.ui-list-groups');
Route::get('/ui/modals', [Modals::class, 'index'])->name('demo-vuexy.ui-modals');
Route::get('/ui/navbar', [Navbar::class, 'index'])->name('demo-vuexy.ui-navbar');
Route::get('/ui/offcanvas', [Offcanvas::class, 'index'])->name('demo-vuexy.ui-offcanvas');
Route::get('/ui/pagination-breadcrumbs', [PaginationBreadcrumbs::class, 'index'])->name('demo-vuexy.ui-pagination-breadcrumbs');
Route::get('/ui/progress', [Progress::class, 'index'])->name('demo-vuexy.ui-progress');
Route::get('/ui/spinners', [Spinners::class, 'index'])->name('demo-vuexy.ui-spinners');
Route::get('/ui/tabs-pills', [TabsPills::class, 'index'])->name('demo-vuexy.ui-tabs-pills');
Route::get('/ui/toasts', [Toasts::class, 'index'])->name('demo-vuexy.ui-toasts');
Route::get('/ui/tooltips-popovers', [TooltipsPopovers::class, 'index'])->name('demo-vuexy.ui-tooltips-popovers');
Route::get('/ui/typography', [Typography::class, 'index'])->name('demo-vuexy.ui-typography');

// extended ui
Route::get('/extended/ui-avatar', [Avatar::class, 'index'])->name('demo-vuexy.extended-ui-avatar');
Route::get('/extended/ui-blockui', [BlockUI::class, 'index'])->name('demo-vuexy.extended-ui-blockui');
Route::get('/extended/ui-drag-and-drop', [DragAndDrop::class, 'index'])->name('demo-vuexy.extended-ui-drag-and-drop');
Route::get('/extended/ui-media-player', [MediaPlayer::class, 'index'])->name('demo-vuexy.extended-ui-media-player');
Route::get('/extended/ui-perfect-scrollbar', [PerfectScrollbar::class, 'index'])->name('demo-vuexy.extended-ui-perfect-scrollbar');
Route::get('/extended/ui-star-ratings', [StarRatings::class, 'index'])->name('demo-vuexy.extended-ui-star-ratings');
Route::get('/extended/ui-sweetalert2', [SweetAlert::class, 'index'])->name('demo-vuexy.extended-ui-sweetalert2');
Route::get('/extended/ui-text-divider', [TextDivider::class, 'index'])->name('demo-vuexy.extended-ui-text-divider');
Route::get('/extended/ui-timeline-basic', [TimelineBasic::class, 'index'])->name('demo-vuexy.extended-ui-timeline-basic');
Route::get('/extended/ui-timeline-fullscreen', [TimelineFullscreen::class, 'index'])->name('demo-vuexy.extended-ui-timeline-fullscreen');
Route::get('/extended/ui-tour', [Tour::class, 'index'])->name('demo-vuexy.extended-ui-tour');
Route::get('/extended/ui-treeview', [Treeview::class, 'index'])->name('demo-vuexy.extended-ui-treeview');
Route::get('/extended/ui-misc', [Misc::class, 'index'])->name('demo-vuexy.extended-ui-misc');

// icons
Route::get('/icons/tabler', [Tabler::class, 'index'])->name('demo-vuexy.icons-tabler');
Route::get('/icons/font-awesome', [FontAwesome::class, 'index'])->name('demo-vuexy.icons-font-awesome');

// form elements
Route::get('/forms/basic-inputs', [BasicInput::class, 'index'])->name('demo-vuexy.forms-basic-inputs');
Route::get('/forms/input-groups', [InputGroups::class, 'index'])->name('demo-vuexy.forms-input-groups');
Route::get('/forms/custom-options', [CustomOptions::class, 'index'])->name('demo-vuexy.forms-custom-options');
Route::get('/forms/editors', [Editors::class, 'index'])->name('demo-vuexy.forms-editors');
Route::get('/forms/file-upload', [FileUpload::class, 'index'])->name('demo-vuexy.forms-file-upload');
Route::get('/forms/pickers', [Picker::class, 'index'])->name('demo-vuexy.forms-pickers');
Route::get('/forms/selects', [Selects::class, 'index'])->name('demo-vuexy.forms-selects');
Route::get('/forms/sliders', [Sliders::class, 'index'])->name('demo-vuexy.forms-sliders');
Route::get('/forms/switches', [Switches::class, 'index'])->name('demo-vuexy.forms-switches');
Route::get('/forms/extras', [Extras::class, 'index'])->name('demo-vuexy.forms-extras');

// form layouts
Route::get('/form/layouts-vertical', [VerticalForm::class, 'index'])->name('demo-vuexy.form-layouts-vertical');
Route::get('/form/layouts-horizontal', [HorizontalForm::class, 'index'])->name('demo-vuexy.form-layouts-horizontal');
Route::get('/form/layouts-sticky', [StickyActions::class, 'index'])->name('demo-vuexy.form-layouts-sticky');

// form wizards
Route::get('/form/wizard-numbered', [FormWizardNumbered::class, 'index'])->name('demo-vuexy.form-wizard-numbered');
Route::get('/form/wizard-icons', [FormWizardIcons::class, 'index'])->name('demo-vuexy.form-wizard-icons');
Route::get('/form/validation', [Validation::class, 'index'])->name('demo-vuexy.form-validation');

// tables
Route::get('/tables/basic', [TablesBasic::class, 'index'])->name('demo-vuexy.tables-basic');
Route::get('/tables/datatables-basic', [DatatableBasic::class, 'index'])->name('demo-vuexy.tables-datatables-basic');
Route::get('/tables/datatables-advanced', [DatatableAdvanced::class, 'index'])->name('demo-vuexy.tables-datatables-advanced');
Route::get('/tables/datatables-extensions', [DatatableExtensions::class, 'index'])->name('demo-vuexy.tables-datatables-extensions');

// charts
Route::get('/charts/apex', [ApexCharts::class, 'index'])->name('demo-vuexy.charts-apex');
Route::get('/charts/chartjs', [ChartJs::class, 'index'])->name('demo-vuexy.charts-chartjs');

// maps
Route::get('/maps/leaflet', [Leaflet::class, 'index'])->name('demo-vuexy.maps-leaflet');

// laravel example
Route::get('/laravel/user-management', [UserManagement::class, 'UserManagement'])->name('demo-vuexy.laravel-example-user-management');
Route::resource('/user-list', UserManagement::class);
