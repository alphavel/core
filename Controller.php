<?php

namespace Alphavel\Core;

abstract class Controller
{
    protected function success(mixed $data = null, int $status = 200): Response
    {
        return Response::success($data, $status);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): Response
    {
        return Response::error($message, $status, $errors);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::make()->json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::make()->redirect($url, $status);
    }
}
