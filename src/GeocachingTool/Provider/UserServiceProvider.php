<?php

namespace GeocachingTool\Provider;

use GeocachingTool\User;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['user.cookie_expiration_time'] = time() + 60 * 60 * 24 * 365 * 100; // 100 years from now

        $app['user'] = function ($app) {
            return new User($app);
        };
    }
}
