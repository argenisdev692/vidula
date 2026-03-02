<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Application\Commands\SendOtp\SendOtpCommand;
use Modules\Auth\Application\Commands\SendOtp\SendOtpHandler;
use Modules\Auth\Application\Commands\VerifyOtp\VerifyOtpCommand;
use Modules\Auth\Application\Commands\VerifyOtp\VerifyOtpHandler;
use Modules\Auth\Domain\Exceptions\InvalidOtpException;
use Modules\Auth\Domain\Exceptions\UserNotFoundException;
use Modules\Auth\Infrastructure\Http\Requests\SendOtpRequest;
use Modules\Auth\Infrastructure\Http\Requests\VerifyOtpRequest;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Tu API",
 *     description="Documentación de la API"
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 */
final class OtpController extends Controller
{
    public function __construct(
        private readonly SendOtpHandler $sendOtpHandler,
        private readonly VerifyOtpHandler $verifyOtpHandler,
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/auth/otp/send",
     *     summary="Send OTP code via Email or SMS",
     *     tags={"Auth"},
     *     @OA\Response(response=200, description="OTP sent")
     * )
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        try {
            $this->sendOtpHandler->handle(
                new SendOtpCommand(
                    identifier: $request->validated('identifier'),
                ),
            );
        } catch (UserNotFoundException) {
            // OWASP best practice: don't reveal existence
        }

        return response()->json([
            'message' => 'If the account exists, an OTP has been sent.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/otp/verify",
     *     summary="Verify OTP code and retrieve access token",
     *     tags={"Auth"},
     *     @OA\Response(response=200, description="Returns Sanctum access token"),
     *     @OA\Response(response=401, description="Invalid OTP or user")
     * )
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $result = $this->verifyOtpHandler->handle(
                new VerifyOtpCommand(
                    identifier: $request->validated('identifier'),
                    code: $request->validated('code'),
                    ipAddress: $request->ip() ?? 'unknown',
                    userAgent: $request->userAgent() ?? 'unknown',
                ),
            );

            // Infrastructure concern: Create personal access token (Sanctum)
            /** @var \Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel&\Laravel\Sanctum\HasApiTokens $userModel */
            $userModel = \Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserEloquentModel::find($result['user']->id);
            $token = $userModel->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Authentication successful.',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (UserNotFoundException | InvalidOtpException $e) {
            return response()->json([
                'message' => 'Invalid credentials or OTP code.',
            ], 401);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Logout and revoke current token",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Logged out successfully")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();
        $token->delete();

        return response()->json([

            'message' => 'Logged out successfully.',
        ]);
    }
}
