<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginAndNavigationTest extends DuskTestCase
{
    /**
     * Test user authentication and navigation to ERP dashboard.
     */
    public function test_user_can_login_and_view_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Masuk ke Sistem')
                ->assertSee('Masuk ke Sistem')
                ->assertTitleContains('KAFEINA ERP')
                ->screenshot('01_login_page')
                ->type('email', 'admin@kafeina.id')
                ->type('password', 'password123')
                ->press('Masuk ke Sistem Kafeina')
                ->waitForLocation('/dashboard', 10)
                ->assertPathIs('/dashboard')
                ->assertTitleContains('KAFEINA')
                ->screenshot('02_dashboard_overview');
        });
    }

    /**
     * Test navigation to POS Cashier interface.
     */
    public function test_user_can_access_pos_terminal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/pos')
                ->pause(1500)
                ->screenshot('03_pos_terminal');
        });
    }
}
