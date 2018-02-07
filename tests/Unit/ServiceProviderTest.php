<?php
declare(strict_types=1);

namespace Tests\Unit;

use Blade;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

class ServiceProviderTest extends TestCase
{

    /** @test */
    public function check_if_commands_registered()
    {
        $commands = [
            'command.lodash.clear-all'     => 'clear-all',
            'command.lodash.db.clear'      => 'db:clear',
            'command.lodash.db.dump'       => 'db:dump',
            'command.lodash.db.restore'    => 'db:restore',
            'command.lodash.log.clear'     => 'log:clear',
            'command.lodash.user.add'      => 'user:add',
            'command.lodash.user.password' => 'user:password',
        ];

        $registered = $this->app[Kernel::class]->all();
        foreach ($commands as $command => $name) {
            $this->assertTrue($this->app->bound($command));
            $this->assertContains($name, array_keys($registered));
        }
    }

    /** @test */
    public function check_if_request_has_macros()
    {
        $this->assertTrue(Request::hasMacro('getInt'));
        $this->assertTrue(Request::hasMacro('getBool'));
        $this->assertTrue(Request::hasMacro('getFloat'));
        $this->assertTrue(Request::hasMacro('getString'));
    }

    /** @test */
    public function check_blade_directives()
    {
        $directives = [
            'datetime',
            'plural',
        ];

        $registered = Blade::getCustomDirectives();
        foreach ($directives as $directive) {
            $this->assertContains($directive, array_keys($registered));
        }
    }

}
