<?php
declare(strict_types=1);
use Core\Router;
/** @var Router $router */

$router->get ('/',            'Controllers\AuthController@showLogin');
$router->get ('/login',       'Controllers\AuthController@showLogin');
$router->post('/login',       'Controllers\AuthController@login');
$router->get ('/logout',      'Controllers\AuthController@logout');
$router->get ('/set-password', 'Controllers\\PasswordResetController@showForm');
$router->post('/set-password', 'Controllers\\PasswordResetController@processForm');

$router->get('/dashboard',    'Controllers\DashboardController@index');

$router->get ('/stores',              'Controllers\StoreController@index');
$router->get ('/stores/search',       'Controllers\StoreController@search');
$router->post('/stores/save',              'Controllers\StoreController@save');
$router->post('/stores/sync-work-hours',   'Controllers\StoreController@syncWorkHours');
$router->post('/stores/{id}/toggle',       'Controllers\StoreController@toggleActive');
$router->get ('/stores/id/{id}',      'Controllers\StoreController@showById');
$router->get ('/stores/{sNum}',       'Controllers\StoreController@show');

$router->get('/crm',              'Controllers\CrmController@index');

$router->get ('/tasks',           'Controllers\TaskController@index');
$router->post('/tasks/create',    'Controllers\TaskController@create');
$router->post('/tasks/{id}/close','Controllers\TaskController@close');

$router->get ('/support',              'Controllers\SupportController@index');
$router->get ('/support/cat/{id}',     'Controllers\SupportController@category');
$router->get ('/support/issues',       'Controllers\SupportController@manageIssues');
$router->post('/support/issues',       'Controllers\SupportController@addIssue');

$router->get ('/users',                  'Controllers\\UserController@index');
$router->post('/users/save',             'Controllers\\UserController@save');
$router->post('/users/toggle',           'Controllers\\UserController@toggle');
$router->post('/users/send-reset-email', 'Controllers\\UserController@sendResetEmail');
$router->get ('/users/perm-groups',      'Controllers\\UserController@permGroups');
$router->post('/users/perm-groups/save', 'Controllers\\UserController@savePermGroup');
$router->get ('/users/{id}',             'Controllers\\UserController@show');
$router->get ('/api/users/search',       'Controllers\\UserController@apiSearch');

$router->get ('/preferences',      'Controllers\PreferencesController@index');
$router->post('/preferences/save', 'Controllers\PreferencesController@save');
$router->get ('/preferences/get',  'Controllers\PreferencesController@getPrefs');

$router->get ('/contacts',              'Controllers\ContactController@index');
$router->post('/contacts/save',         'Controllers\ContactController@save');
$router->post('/contacts/{id}/toggle',  'Controllers\ContactController@toggle');
$router->get ('/api/contacts',          'Controllers\ContactController@apiSearch');
$router->get ('/api/contacts/list',     'Controllers\ContactController@apiList');

// Area Managers
$router->get ('/area-managers',                         'Controllers\\AreaManagerController@index');
$router->post('/area-managers/save',                    'Controllers\\AreaManagerController@save');
$router->post('/area-managers/{id}/toggle',             'Controllers\\AreaManagerController@toggle');
$router->post('/area-managers/{id}/delete',             'Controllers\\AreaManagerController@delete');
$router->get ('/api/area-managers',                     'Controllers\\AreaManagerController@apiList');
$router->get ('/api/area-managers/for-store/{storeId}', 'Controllers\\AreaManagerController@apiForStore');
$router->get ('/api/area-managers/{id}/stores',         'Controllers\\AreaManagerController@apiStores');
$router->post('/api/area-managers/{id}/assign',         'Controllers\\AreaManagerController@apiAssign');
$router->post('/api/area-managers/{id}/unassign',       'Controllers\\AreaManagerController@apiUnassign');

$router->get ('/api/wize/call',    'Controllers\\WizenetController@getCall');
$router->get ('/api/wize/search',  'Controllers\\WizenetController@search');

// Formatter
$router->get ('/formatter',              'Controllers\\FormatterController@index');
$router->get ('/formatter/editor',       'Controllers\\FormatterController@editor');
$router->post('/formatter/save',         'Controllers\\FormatterController@save');
$router->post('/formatter/toggle',       'Controllers\\FormatterController@toggle');
$router->post('/formatter/delete',       'Controllers\\FormatterController@delete');
$router->get ('/api/formatter/template', 'Controllers\\FormatterController@getTemplate');
$router->get ('/api/formatter/list',     'Controllers\\FormatterController@apiList');
$router->get ('/api/formatter/stores',   'Controllers\\FormatterController@apiStores');

$router->get ('/nav-manager',          'Controllers\NavManagerController@index');
$router->post('/nav-manager/save',     'Controllers\NavManagerController@save');
$router->post('/nav-manager/toggle',   'Controllers\NavManagerController@toggle');
$router->post('/nav-manager/reorder',  'Controllers\NavManagerController@reorder');

$router->post('/api/nav',              'Controllers\NavController@getNav');
$router->get ('/api/stores',           'Controllers\StoreController@apiSearch');
$router->get ('/api/stores/{sNum}',    'Controllers\StoreController@apiGet');
$router->post('/api/support/issues',   'Controllers\SupportController@issues');
$router->get ('/api/support/search',   'Controllers\SupportController@searchProduct');

// Activity Log
$router->get('/activity-log',     'Controllers\\ActivityLogController@index');
$router->get('/api/activity-log', 'Controllers\\ActivityLogController@apiList');

// Automation (CronJob)
$router->get ('/automation',              'Controllers\\AutomationController@index');
$router->get ('/api/automation',          'Controllers\\AutomationController@apiList');
$router->get ('/api/automation/statuses', 'Controllers\\AutomationController@apiStatuses');
$router->post('/automation/create',       'Controllers\\AutomationController@create');
$router->post('/automation/{id}/cancel',  'Controllers\\AutomationController@cancel');

// Products & Inventory — global search + Formatter
$router->get ('/api/products',  'Controllers\\FormatterController@apiProducts');
$router->get ('/api/inventory', 'Controllers\\FormatterController@apiInventory');

// ── CRM API ──────────────────────────────────────────────────────────────────
$router->get ('/api/crm/calls',   'Controllers\\CrmController@apiCalls');
$router->get ('/api/crm/service', 'Controllers\\CrmController@apiService');
$router->get ('/api/crm/notes',   'Controllers\\CrmController@apiNotes');
$router->post('/api/crm/note',    'Controllers\\CrmController@apiSaveNote');
$router->post('/api/crm/wa',      'Controllers\\CrmController@apiSendWa');

// Accounts (Support Passwords)
$router->get ('/accounts',                  'Controllers\\AccountController@index');
$router->get ('/api/accounts',              'Controllers\\AccountController@apiList');
$router->post('/api/accounts/create',       'Controllers\\AccountController@create');
$router->post('/api/accounts/{id}/delete',  'Controllers\\AccountController@delete');
$router->post('/api/accounts/{id}/update',  'Controllers\\AccountController@update');

// Duty Management
$router->get ('/duty',                          'Controllers\\DutyController@index');
$router->get ('/api/duty/reps',                 'Controllers\\DutyController@apiRepsList');
$router->post('/api/duty/reps',                 'Controllers\\DutyController@apiRepsCreate');
$router->post('/api/duty/reps/{id}',            'Controllers\\DutyController@apiRepsUpdate');
$router->post('/api/duty/reps/{id}/delete',     'Controllers\\DutyController@apiRepsDelete');
$router->get ('/api/duty/schedule',             'Controllers\\DutyController@apiScheduleList');
$router->post('/api/duty/schedule/auto',        'Controllers\\DutyController@apiScheduleAuto');
$router->post('/api/duty/schedule/manual',      'Controllers\\DutyController@apiScheduleManual');
$router->post('/api/duty/schedule/{id}',        'Controllers\\DutyController@apiScheduleSave');
$router->post('/api/duty/schedule/{id}/delete', 'Controllers\\DutyController@apiScheduleDelete');
$router->get ('/api/duty/users',                'Controllers\\DutyController@apiUsersList');
$router->get ('/api/duty/guidance',             'Controllers\\DutyController@apiGuidanceList');
$router->post('/api/duty/guidance',             'Controllers\\DutyController@apiGuidanceSave');
$router->get ('/api/duty/current',              'Controllers\\DutyController@apiCurrentWeek');
$router->get ('/duty/signage',                  'Controllers\\DutyController@signage');

// Lab Inventory
$router->get ('/lab',                        'Controllers\\LabController@index');
$router->get ('/api/lab/inventory',          'Controllers\\LabController@apiInventory');
$router->get ('/api/lab/history',            'Controllers\\LabController@apiHistory');
$router->get ('/api/lab/history-chart',      'Controllers\\LabController@apiHistoryChart');
$router->post('/api/lab/movement',           'Controllers\\LabController@apiMovement');
$router->post('/api/lab/movement/approve',   'Controllers\\LabController@apiApproveMovement');
$router->post('/api/lab/item/update',        'Controllers\\LabController@apiUpdateItem');
$router->post('/api/lab/item/add',           'Controllers\\LabController@apiAddItem');
$router->post('/api/lab/import',             'Controllers\\LabController@apiImport');
$router->get ('/api/lab/users',              'Controllers\\LabController@apiUsersList');
$router->post('/api/lab/user/add',           'Controllers\\LabController@apiAddUser');
$router->post('/api/lab/user/toggle',        'Controllers\\LabController@apiToggleUser');

// Invoice Change Name
$router->get ('/invoice-change-name',                 'Controllers\\InvoiceChangeNameController@index');
$router->get ('/api/invoice-change-name',              'Controllers\\InvoiceChangeNameController@apiList');
$router->post('/api/invoice-change-name/create',       'Controllers\\InvoiceChangeNameController@create');
$router->post('/api/invoice-change-name/{id}/status',  'Controllers\\InvoiceChangeNameController@updateStatus');
$router->post('/api/invoice-change-name/{id}/edit',    'Controllers\\InvoiceChangeNameController@editField');