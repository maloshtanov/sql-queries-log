<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class SqlQueriesLogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (env('APP_DEBUG') && request()->has('sql')) {
            DB::connection()->enableQueryLog();

            Event::listen(RequestHandled::class, function (RequestHandled $event) {

                if ($event->response->isSuccessful()) {
                    $queries = array_map(function ($sql) {
                        return array_reduce($sql['bindings'], function ($sql, $binding) {
                            return preg_replace('/\?/', $this->prepareType($binding), $sql, 1);
                        }, $sql['query']);
                    }, DB::getQueryLog());


                    if ($event->request->expectsJson()) {
                        if ($event->response instanceof JsonResponse) {
                            $event->response->setContent(json_encode(
                                array_merge(
                                    compact('queries'),
                                    json_decode($event->response->getContent(), true)
                                )
                            ));
                        } else {
                                $event->response->setContent(compact('queries'));
                                $event->response->setStatusCode(200);
                        }
                    } else {
                        dump($queries);
                    }
                }
            });

//            Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
//                dump(
//                    array_reduce($query->bindings, function ($sql, $binding) {
//                        return preg_replace('/\?/', $this->prepareType($binding), $sql, 1);
//                    }, $query->sql)
//                );
//            });
        }
    }

    protected function prepareType($value)
    {
        return is_numeric($value) ? $value : "'" . $value . "'";
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
