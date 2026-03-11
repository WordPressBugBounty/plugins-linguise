<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die(); // @codeCoverageIgnore

class HttpResponse
{
    static function errorJSON($message, $code = 500)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'error' => true,
            'message' => $message
        ]);
        Helper::stop();
    }

    static function successJSON($data, $message = '', $code = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode([
            'error' => false,
            'message' => $message,
            'data' => $data
        ]);
        Helper::stop();
    }

    static function rejectGET()
    {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(403);
        echo '<h1>403 Forbidden</h1>';
        echo '<p>You are not allowed to access this page.</p>';
        Helper::stop();
    }

    static function unknownGETAction()
    {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(400);
        echo '<h1>400 Bad Request</h1>';
        echo '<p>Unknown action.</p>';
        Helper::stop();
    }
}
