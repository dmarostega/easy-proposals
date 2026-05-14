<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserAccountController;
use App\Http\Controllers\Admin\UserCustomerController as AdminUserCustomerController;
use App\Http\Controllers\Admin\UserProposalController as AdminUserProposalController;
use App\Http\Controllers\Admin\UserServiceItemController as AdminUserServiceItemController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\App\AppPageController;
use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\ProposalController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\ServiceItemController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PublicProposalController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/recursos', [PageController::class, 'features'])->name('features');
Route::get('/precos', [PageController::class, 'pricing'])->name('pricing');
Route::get('/termos-de-uso', [PageController::class, 'terms'])->name('terms');
Route::get('/politica-de-privacidade', [PageController::class, 'privacy'])->name('privacy');
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisteredUserController::class, 'store']);
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/p/{token}', [PublicProposalController::class, 'show'])->name('public.proposals.show');
Route::post('/p/{token}/aprovar', [PublicProposalController::class, 'approve'])->name('public.proposals.approve');
Route::post('/p/{token}/recusar', [PublicProposalController::class, 'reject'])->name('public.proposals.reject');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/app', AppPageController::class)->name('app');
    Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::apiResource('clientes', CustomerController::class)->parameters(['clientes' => 'customer']);
    Route::apiResource('servicos', ServiceItemController::class)->parameters(['servicos' => 'serviceItem']);
    Route::apiResource('propostas', ProposalController::class)->parameters(['propostas' => 'proposal']);
    Route::post('/propostas/{proposal}/enviar', [ProposalController::class, 'send'])->name('propostas.send');
    Route::get('/propostas/{proposal}/pdf', [ProposalController::class, 'pdf'])->name('propostas.pdf');
});

Route::prefix('admin')->as('admin.')->middleware(['auth', 'active', 'admin'])->group(function (): void {
    Route::get('/', AdminPageController::class)->name('index');
    Route::get('/relatorios', ReportController::class)->name('reports');
    Route::apiResource('planos', AdminPlanController::class)->parameters(['planos' => 'plan']);
    Route::apiResource('usuarios', AdminUserController::class)->parameters(['usuarios' => 'user'])->only(['index', 'show', 'update', 'destroy']);
    Route::prefix('usuarios/{user}')->as('usuarios.')->group(function (): void {
        Route::get('/dashboard', [UserAccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/perfil', [UserAccountController::class, 'profile'])->name('profile.show');
        Route::put('/perfil', [UserAccountController::class, 'updateProfile'])->name('profile.update');
        Route::get('/auditoria', [UserAccountController::class, 'auditLogs'])->name('audit');
        Route::apiResource('clientes', AdminUserCustomerController::class)->parameters(['clientes' => 'customer']);
        Route::apiResource('servicos', AdminUserServiceItemController::class)->parameters(['servicos' => 'serviceItem']);
        Route::apiResource('propostas', AdminUserProposalController::class)->parameters(['propostas' => 'proposal']);
        Route::post('/propostas/{proposal}/enviar', [AdminUserProposalController::class, 'send'])->name('propostas.send');
        Route::get('/propostas/{proposal}/pdf', [AdminUserProposalController::class, 'pdf'])->name('propostas.pdf');
    });
    Route::get('/configuracoes', [AppSettingController::class, 'index'])->name('settings.index');
    Route::put('/configuracoes', [AppSettingController::class, 'update'])->name('settings.update');
});
