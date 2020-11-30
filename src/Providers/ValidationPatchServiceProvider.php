<?php

namespace ValidationPatch\Providers;

use Illuminate\Translation\Translator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Validation\{Factory as ValidatorFactory};
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use ValidationPatch\Validation\{Validator as ValidatorOverride, Factory as ValidationFactoryOverride};

/**
 * Class ValidationPatchServiceProvider
 * @package ValidationPatch\Providers
 */
class ValidationPatchServiceProvider extends ServiceProvider
{
    /**
     * @void
     */
    public function boot()
    {
        $this->app->extend('validator', function (ValidatorFactory $factory, Application $app) {
            $validator = new ValidationFactoryOverride($app['translator'], $app);
            $validator->setPresenceVerifier($app['validation.presence']);
            $resolver = function (Translator $translator, array $data, array $rules, array $messages, array $custom) {
                return new ValidatorOverride($translator, $data, $rules, $messages, $custom);
            };
            $validator->resolver($resolver);

            return $validator;
        });

        ValidatorFacade::swap(app('validator'));
    }
}
