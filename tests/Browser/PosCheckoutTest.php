<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PosCheckoutTest extends DuskTestCase
{
    /**
     * Test POS Cashier terminal interaction and shift modal verification.
     */
    public function test_cashier_can_interact_with_pos_and_modal(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->waitForText('Masuk ke Sistem')
                ->type('email', 'admin@kafeina.id')
                ->type('password', 'password123')
                ->press('Masuk ke Sistem Kafeina')
                ->waitForLocation('/dashboard', 10)
                ->visit('/pos')
                ->waitForText('Riwayat Order', 10)
                ->assertTitleContains('Terminal POS')
                ->assertSee('Riwayat Order')
                ->assertSee('Detail Shift')
                ->screenshot('04_pos_touch_terminal')
                ->press('Detail Shift')
                ->pause(800)
                ->screenshot('05_pos_shift_modal');
        });
    }
}
