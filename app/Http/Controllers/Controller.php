<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="VIDULA API",
 *     description="REST API — Sanctum token auth"
 * )
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="API Server")
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
abstract class Controller
{
    //
}
