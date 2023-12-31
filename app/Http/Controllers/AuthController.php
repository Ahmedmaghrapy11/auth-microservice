<?php

namespace App\Http\Controllers;

use App\Events\UserAuthenticated;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Event;

class AuthController extends Controller
{

    protected $rabbitMQService;

    public function __construct(RabbitMQService $rabbitMQService)
    {
        $this->rabbitMQService = $rabbitMQService;
    }

/**
 * @OA\Info(
 *      title="Authentication Microservice",
 *      version="1.0.0",
 *      description="Documentation for the Authentication Microservice API.",
 *      termsOfService="http://example.com/terms/",
 *      @OA\Contact(
 *          email="contact@example.com",
 *          name="API Support"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 */

 /**
 * @OA\Post(
 *      path="/register",
 *      operationId="register",
 *      tags={"Authentication"},
 *      summary="Register a new user",
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(
 *              required={"name", "email", "password"},
 *              @OA\Property(property="name", type="string", example="John Doe"),
 *              @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *              @OA\Property(property="password", type="string", format="password", example="password"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful registration",
 *          @OA\JsonContent(
 *              @OA\Property(property="token", type="string", format="JWT", example="your_generated_token"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=422,
 *          description="Validation error",
 *          @OA\JsonContent(
 *              @OA\Property(property="error", type="object", example={"field_name": {"The field name is required."}}),
 *          ),
 *      ),
 *      @OA\Info(
 *          title="Authentication Microservice",
 *          version="1.0.0",
 *          description="Documentation for the Authentication Microservice API.",
 *          termsOfService="http://example.com/terms/",
 *          @OA\Contact(
 *              email="contact@example.com",
 *              name="API Support"
 *          ),
 *          @OA\License(
 *              name="Apache 2.0",
 *              url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *          )
 *      )
 * )
 */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required','string','confirmed','min:8','max:32', 'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[*!$#%]).*$/',],
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
        ]);
        $token = JWTAuth::fromUser($user);
        $this->rabbitMQService->connect();
        $channel = $this->rabbitMQService->channel();
        $channel->exchangeDeclare('authentication_exchange', 'direct', false, true, false);
        $channel->publish('User authenticated', [], 'authentication_queue');
        $this->rabbitMQService->disconnect();
        Event::dispatch(new UserAuthenticated($user->id));
        Log::info("UserAuthenticated event dispatched for user ID: " . $user->id);
        return response()->json(['message' => 'A new user has registered successfully!', 'token' => $token]);
    }

    /**
 * @OA\Post(
 *      path="/login",
 *      operationId="login",
 *      tags={"Authentication"},
 *      summary="Authenticate user and get JWT token",
 *      @OA\RequestBody(
 *          required=true,
 *          @OA\JsonContent(
 *              required={"email", "password"},
 *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *              @OA\Property(property="password", type="string", format="password", example="password"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="Successful login",
 *          @OA\JsonContent(
 *              @OA\Property(property="token", type="string", format="JWT", example="your_generated_token"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Invalid credentials",
 *      ),
 *      @OA\Info(
 *          title="Authentication Microservice",
 *          version="1.0.0",
 *          description="Documentation for the Authentication Microservice API.",
 *          termsOfService="http://example.com/terms/",
 *          @OA\Contact(
 *              email="contact@example.com",
 *              name="API Support"
 *          ),
 *          @OA\License(
 *              name="Apache 2.0",
 *              url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *          )
 *      )
 * )
 */
    public function login(Request $request) {
        $data = $request->only("email","password");
        if (!$token = JWTAuth::attempt($data)) {
            return response()->json(["error"=> "Invalid email or password, Please try again !"], 401);
        }
        $userId = auth()->user()->id;
        Event::dispatch(new UserAuthenticated($userId));
        return response()->json(["message"=> "Logged in successfully!", 'token' => $token]);
    }

    /**
 * @OA\Post(
 *      path="/refresh-token",
 *      operationId="refreshToken",
 *      tags={"Authentication"},
 *      summary="Refresh the JWT token",
 *      security={{"bearerAuth": {}}},
 *      @OA\Response(
 *          response=200,
 *          description="Token refreshed successfully",
 *          @OA\JsonContent(
 *              @OA\Property(property="token", type="string", format="JWT", example="your_refreshed_token"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 *      ),
 *      @OA\Response(
 *          response=500,
 *          description="Internal Server Error",
 *          @OA\JsonContent(
 *              @OA\Property(property="error", type="string", example="Internal Server Error"),
 *          ),
 *      ),
 *      @OA\Info(
 *          title="Authentication Microservice",
 *          version="1.0.0",
 *          description="Documentation for the Authentication Microservice API.",
 *          termsOfService="http://example.com/terms/",
 *          @OA\Contact(
 *              email="contact@example.com",
 *              name="API Support"
 *          ),
 *          @OA\License(
 *              name="Apache 2.0",
 *              url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *          )
 *      )
 * )
 */

    /**
     * Refresh a user's token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            // Attempt to refresh the token
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            // If the token refresh is successful, return the new token
            return response()->json(['message' => 'Token refreshed successfully !','token' => $newToken]);
        } catch (JWTException $e) {
            // If an exception occurs during token refresh, return an error response
            return response()->json(['error' => 'Unauthorized action'], 401);
        }
    }

    /**
 * @OA\Post(
 *      path="/logout",
 *      operationId="logout",
 *      tags={"Authentication"},
 *      summary="Logout the authenticated user",
 *      security={{"bearerAuth": {}}},
 *      @OA\Response(
 *          response=200,
 *          description="Successfully logged out",
 *          @OA\JsonContent(
 *              @OA\Property(property="message", type="string", example="Successfully logged out"),
 *          ),
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated",
 *      ),
 *      @OA\Info(
 *          title="Authentication Microservice",
 *          version="1.0.0",
 *          description="Documentation for the Authentication Microservice API.",
 *          termsOfService="http://example.com/terms/",
 *          @OA\Contact(
 *              email="contact@example.com",
 *              name="API Support"
 *          ),
 *          @OA\License(
 *              name="Apache 2.0",
 *              url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *          )
 *      )
 * )
 */
    public function logout()
    {
        JWTAuth::invalidate();
        return response()->json(['message' => 'Logged out successfully !']);
    }
}
