<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventCashierWrite;
use App\Models\MposUser;
use App\Models\Muser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CashierReadOnlyWebTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('mpos_user_spv');
        Schema::dropIfExists('mpos_user');
        Schema::dropIfExists('muser');

        Schema::create('muser', function (Blueprint $table): void {
            $table->increments('nid');
            $table->string('cname')->nullable();
            $table->string('cemail')->nullable();
            $table->string('cpassword')->nullable();
            $table->boolean('fadmin')->default(false);
            $table->boolean('fsuper')->default(false);
            $table->boolean('factive')->default(true);
        });

        Schema::create('mpos_user', function (Blueprint $table): void {
            $table->increments('nid');
            $table->unsignedInteger('nid_user');
            $table->unsignedInteger('nid_outlet')->nullable();
            $table->boolean('fowner')->default(false);
            $table->boolean('fspv')->default(false);
            $table->boolean('fcashier')->default(false);
            $table->boolean('fcaptain')->default(false);
            $table->boolean('factive')->default(true);
        });

        Schema::create('mpos_user_spv', function (Blueprint $table): void {
            $table->increments('nid');
            $table->unsignedInteger('nid_user');
            $table->unsignedInteger('nid_outlet');
        });
    }

    private function createUserWithPosFlags(array $posFlags, array $userAttributes = []): Muser
    {
        $user = Muser::create(array_merge([
            'cname' => 'Test User',
            'cemail' => 'test' . uniqid() . '@example.com',
            'cpassword' => bcrypt('password'),
            'fadmin' => false,
            'fsuper' => false,
            'factive' => true,
        ], $userAttributes));

        MposUser::create(array_merge([
            'nid_user' => $user->nid,
            'nid_outlet' => 1,
            'fowner' => false,
            'fspv' => false,
            'fcashier' => false,
            'fcaptain' => false,
            'factive' => true,
        ], $posFlags));

        return $user->fresh();
    }

    public function test_cashier_level_detection_distinguishes_roles(): void
    {
        $cashier = $this->createUserWithPosFlags(['fcashier' => true]);
        $captain = $this->createUserWithPosFlags(['fcaptain' => true]);
        $spv = $this->createUserWithPosFlags(['fspv' => true]);
        $owner = $this->createUserWithPosFlags(['fowner' => true]);
        $ownerAndCashier = $this->createUserWithPosFlags(['fowner' => true, 'fcashier' => true]);

        $this->assertTrue($cashier->isCashierLevel());
        $this->assertFalse($cashier->canModifyWebData());

        $this->assertFalse($captain->isCashierLevel());
        $this->assertTrue($captain->canModifyWebData());

        $this->assertFalse($spv->isCashierLevel());
        $this->assertTrue($spv->canModifyWebData());

        $this->assertFalse($owner->isCashierLevel());
        $this->assertTrue($owner->canModifyWebData());

        $this->assertFalse($ownerAndCashier->isCashierLevel());
        $this->assertTrue($ownerAndCashier->canModifyWebData());
    }

    public function test_middleware_blocks_write_requests_and_edit_pages_for_cashier(): void
    {
        $cashier = $this->createUserWithPosFlags(['fcashier' => true]);
        Auth::login($cashier);

        $middleware = new PreventCashierWrite();

        // POST products.store -> 403
        $postRequest = Request::create('/products', 'POST');
        $postRequest->setRouteResolver(fn () => (new Route('POST', '/products', []))->name('products.store'));

        try {
            $middleware->handle($postRequest, fn () => response('ok'));
            $this->fail('Expected 403 HttpException for Cashier POST request.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // AJAX PUT products.recipes.update -> JSON 403
        $ajaxRequest = Request::create('/products/1/recipes/2', 'PUT', [], [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $ajaxRequest->setRouteResolver(fn () => (new Route('PUT', '/products/{product}/recipes/{recipe}', []))->name('products.recipes.update'));

        $ajaxResponse = $middleware->handle($ajaxRequest, fn () => response()->json(['ok' => true]));
        $this->assertSame(403, $ajaxResponse->getStatusCode());

        // GET categories.edit -> 403
        $editPageRequest = Request::create('/categories/1/edit', 'GET');
        $editPageRequest->setRouteResolver(fn () => (new Route('GET', '/categories/{category}/edit', []))->name('categories.edit'));

        try {
            $middleware->handle($editPageRequest, fn () => response('ok'));
            $this->fail('Expected 403 HttpException for Cashier edit page request.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_middleware_allows_read_and_profile_updates_for_cashier(): void
    {
        $cashier = $this->createUserWithPosFlags(['fcashier' => true]);
        Auth::login($cashier);

        $middleware = new PreventCashierWrite();

        // GET products.index -> allowed
        $getRequest = Request::create('/products', 'GET');
        $getRequest->setRouteResolver(fn () => (new Route('GET', '/products', []))->name('products.index'));
        $this->assertSame(200, $middleware->handle($getRequest, fn () => response('ok'))->getStatusCode());

        // PUT profile.update -> allowed
        $profileRequest = Request::create('/profile', 'PUT');
        $profileRequest->setRouteResolver(fn () => (new Route('PUT', '/profile', []))->name('profile.update'));
        $this->assertSame(200, $middleware->handle($profileRequest, fn () => response('ok'))->getStatusCode());

        // PUT profile.password -> allowed
        $passwordRequest = Request::create('/profile/password', 'PUT');
        $passwordRequest->setRouteResolver(fn () => (new Route('PUT', '/profile/password', []))->name('profile.password'));
        $this->assertSame(200, $middleware->handle($passwordRequest, fn () => response('ok'))->getStatusCode());

        // POST logout -> allowed
        $logoutRequest = Request::create('/logout', 'POST');
        $logoutRequest->setRouteResolver(fn () => (new Route('POST', '/logout', []))->name('logout'));
        $this->assertSame(200, $middleware->handle($logoutRequest, fn () => response('ok'))->getStatusCode());
    }

    public function test_middleware_allows_write_requests_for_non_cashier_roles(): void
    {
        $middleware = new PreventCashierWrite();

        foreach ([['fowner' => true], ['fspv' => true], ['fcaptain' => true]] as $flags) {
            $user = $this->createUserWithPosFlags($flags);
            Auth::login($user);

            $postRequest = Request::create('/products', 'POST');
            $postRequest->setRouteResolver(fn () => (new Route('POST', '/products', []))->name('products.store'));

            $response = $middleware->handle($postRequest, fn () => response('ok'));
            $this->assertSame(200, $response->getStatusCode());
        }
    }
}
