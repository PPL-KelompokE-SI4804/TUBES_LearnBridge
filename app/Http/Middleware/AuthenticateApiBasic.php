<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiBasic
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        $email = $request->getUser();
        $password = $request->getPassword();
        if (! $email) {
            $authorization = $request->header('Authorization', '');
            if (str_starts_with($authorization, 'Basic ')) {
                $decoded = base64_decode(substr($authorization, 6), true);
                if ($decoded !== false && str_contains($decoded, ':')) {
                    [$email, $password] = explode(':', $decoded, 2);
                }
            }
        }
        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user || ! $password || ! Hash::check($password, $user->password)) {
            return response()->json(['message' => 'Kredensial API tidak valid.'], 401, [
                'WWW-Authenticate' => 'Basic realm="LearnBridge API"',
            ]);
        }

        Auth::forgetGuards();
        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
