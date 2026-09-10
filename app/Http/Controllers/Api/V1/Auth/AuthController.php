<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\VerifyLoginOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Authenticate a user. Returns an access token directly, unless the user
     * requires verification — then a login OTP is emailed and the client must
     * call verify-otp to obtain the token.
     */
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Authenticate a user (issues a token, or a login OTP for 2FA users)',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'superadmin@rapnex.test'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Either logged in (token returned) or an OTP was sent for verification.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            examples: [
                'token' => new OA\Examples(
                    example: 'token',
                    summary: 'Standard login (verification not required)',
                    value: [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Logged in successfully.',
                        'data' => [
                            'user' => [
                                'id' => 1,
                                'name' => 'Super Administrator',
                                'email' => 'superadmin@rapnex.test',
                                'phone' => '+10000000000',
                                'email_verified_at' => '04-June-2026 15:01:33',
                            ],
                            'token_type' => 'Bearer',
                            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                        ],
                    ],
                ),
                'otp' => new OA\Examples(
                    example: 'otp',
                    summary: 'Verification required (OTP emailed)',
                    value: [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'A verification code has been sent to your email.',
                        'data' => ['requires_verification' => true],
                    ],
                ),
            ],
        ),
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiError')],
            example: ['status' => 'failed', 'code' => 401, 'message' => 'Invalid credentials.', 'data' => ['errors' => []]],
        ),
    )]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($result['requires_verification']) {
            return ApiResponse::successResponse(
                'A verification code has been sent to your email.',
                ['requires_verification' => true],
            );
        }

        return ApiResponse::successResponse('Logged in successfully.', [
            'user' => new UserResource($result['user']),
            'token_type' => 'Bearer',
            'access_token' => $result['token'],
        ]);
    }

    /**
     * Verify a login OTP and issue an access token.
     */
    #[OA\Post(
        path: '/api/v1/auth/login/verify-otp',
        summary: 'Verify a login OTP and issue an access token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'superadmin@rapnex.test'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Logged in successfully.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: [
                'status' => 'success',
                'code' => 200,
                'message' => 'Logged in successfully.',
                'data' => [
                    'user' => [
                        'id' => 1,
                        'name' => 'Super Administrator',
                        'email' => 'superadmin@rapnex.test',
                        'phone' => '+10000000000',
                        'email_verified_at' => '04-June-2026 15:01:33',
                    ],
                    'token_type' => 'Bearer',
                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                ],
            ],
        ),
    )]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    public function verifyLoginOtp(VerifyLoginOtpRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->authService->verifyLoginOtp(
            $request->validated('email'),
            $request->validated('otp'),
        );

        return ApiResponse::successResponse('Logged in successfully.', [
            'user' => new UserResource($user),
            'token_type' => 'Bearer',
            'access_token' => $token,
        ]);
    }

    /**
     * Issue a password-reset OTP to the user's email.
     */
    #[OA\Post(
        path: '/api/v1/auth/forgot-password',
        summary: 'Request a password-reset OTP',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'superadmin@rapnex.test'),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'OTP sent.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: ['status' => 'success', 'code' => 200, 'message' => 'A password reset OTP has been sent to your email.', 'data' => null],
        ),
    )]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetOtp($request->validated('email'));

        return ApiResponse::successResponse('A password reset OTP has been sent to your email.');
    }

    /**
     * Reset the password using a valid OTP.
     */
    #[OA\Post(
        path: '/api/v1/auth/reset-password',
        summary: 'Reset password using an OTP',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'otp', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'superadmin@rapnex.test'),
                    new OA\Property(property: 'otp', type: 'string', example: '123456'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'new-secret-password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'new-secret-password'),
                ],
            ),
        ),
    )]
    #[OA\Response(
        response: 200,
        description: 'Password reset successfully.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: ['status' => 'success', 'code' => 200, 'message' => 'Your password has been reset successfully.', 'data' => null],
        ),
    )]
    #[OA\Response(response: 422, ref: '#/components/responses/ValidationError')]
    #[OA\Response(response: 429, ref: '#/components/responses/TooManyRequests')]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword(
            $request->validated('email'),
            $request->validated('otp'),
            $request->validated('password'),
        );

        return ApiResponse::successResponse('Your password has been reset successfully.');
    }

    /**
     * Return the currently authenticated user.
     */
    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Return the currently authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Authenticated user retrieved.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: [
                'status' => 'success',
                'code' => 200,
                'message' => 'Authenticated user retrieved.',
                'data' => [
                    'id' => 1,
                    'name' => 'Super Administrator',
                    'email' => 'superadmin@rapnex.test',
                    'phone' => '+10000000000',
                    'email_verified_at' => '04-June-2026 15:01:33',
                ],
            ],
        ),
    )]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::successResponse(
            'Authenticated user retrieved.',
            new UserResource($request->user()),
        );
    }

    /**
     * Revoke the current access token.
     */
    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Revoke the current access token',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Logged out successfully.',
        content: new OA\JsonContent(
            allOf: [new OA\Schema(ref: '#/components/schemas/ApiSuccess')],
            example: ['status' => 'success', 'code' => 200, 'message' => 'Logged out successfully.', 'data' => null],
        ),
    )]
    #[OA\Response(response: 401, ref: '#/components/responses/Unauthorized')]
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return ApiResponse::successResponse('Logged out successfully.');
    }
}
