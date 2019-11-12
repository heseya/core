<?php

namespace App\Http\Controllers\External;

use Auth;
// use SammyK\LaravelFacebookSdk\LaravelFacebookSdk;

class FacebookController extends Controller
{
    public function login(LaravelFacebookSdk $fb)
    {
        // Send an array of permissions to request
        $login_url = $fb->getLoginUrl([
            'manage_pages',
            'pages_messaging',
        ]);

        return redirect($login_url);
    }

    // Endpoint that is redirected to after an authentication attempt
    public function callback(LaravelFacebookSdk $fb)
    {
        // Obtain an access token.
        try {
            $token = $fb->getAccessTokenFromRedirect();
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }

        // Access token will be null if the user denied the request
        // or if someone just hit this URL outside of the OAuth flow.
        if (!$token) {
            // Get the redirect helper
            $helper = $fb->getRedirectLoginHelper();

            if (!$helper->getError()) {
                abort(403, 'Unauthorized action.');
            }

            // User denied the request
            dd(
                $helper->getError(),
                $helper->getErrorCode(),
                $helper->getErrorReason(),
                $helper->getErrorDescription()
            );
        }

        if (!$token->isLongLived()) {
            // OAuth 2.0 client handler
            $oauth_client = $fb->getOAuth2Client();

            // Extend the access token.
            try {
                $token = $oauth_client->getLongLivedAccessToken($token);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                dd($e->getMessage());
            }
        }

        // Zapisywanie tokenu klienta i pierwszego z brzegu tokenu strony
        $user = Auth::user();
        $user->update(['fb' => $token]);

        return redirect('/admin/settings/facebook/pages');
    }

    public function settings(LaravelFacebookSdk $fb)
    {
        $user = Auth::user();

        // Jeśli nie jesteś zalogowany
        if (is_null($user->fb)) {
            return response()->view('admin/settings/facebook-empty', [
                'user' => Auth::user(),
            ]);
        }

        // Jeśli nie wybrano strony
        if (is_null($user->fb_page)) {
            return redirect('/admin/settings/facebook/pages');
        }

        try {
            $response = $fb->get('/me?fields=id,name,picture', $user->fb);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }
        $user_fb = json_decode($response->getGraphUser(), true);

        try {
            $response = $fb->get('/me?fields=id,name,picture', $user->fb_page);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }
        $page = json_decode($response->getGraphUser(), true);

        return response()->view('admin/settings/facebook', [
            'user_fb' => $user_fb,
            'page' => isset($page) ? $page : null,
            'user' => $user,
        ]);
    }

    public function pages(LaravelFacebookSdk $fb)
    {
        $user = Auth::user();

        // Token strony
        try {
            $response = $fb->get('/me/accounts?type=page&fields=name,access_token,picture', $user->fb);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }
        $pages = json_decode($response->getGraphEdge(), true);

        if (count($pages) < 1) {
            return redirect('/admin/settings/facebook/unlink');
        }

        return response()->view('admin/settings/facebook-pages', [
            'pages' => $pages,
            'user' => $user,
        ]);
    }

    public function setPage($access_token)
    {
        $user = Auth::user();
        $user->update([
            'fb_page' => $access_token,
        ]);

        return redirect('admin/settings/facebook');
    }

    // Odlączanie konta
    public function unlink(LaravelFacebookSdk $fb)
    {
        $user = Auth::user();

        if (!is_null($user->fb)) {

            try {
                $fb->delete('/me/permissions', [], $user->fb);
            } catch (Facebook\Exceptions\FacebookSDKException $e) {
                dd($e->getMessage());
            }

            $user->update([
                'fb' => null,
                'fb_page' => null,
            ]);
        }

        return redirect('/admin/settings/facebook', [
            'user' => $user,
        ]);
    }

    public function chats(LaravelFacebookSdk $fb)
    {
        $user = Auth::user();

        // Jeśli nie jesteś zalogowany do fb
        if (is_null($user->fb_page)) {
            return redirect('/admin/settings/facebook');
        }

        try {
            $response = $fb->get('/me/conversations?fields=id,unread_count,snippet,participants,user_id', $user->fb_page);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }

        $chats = $response->getGraphEdge();

        // return response($chats);

        // foreach($response->getGraphEdge() as $chat) {

        //   $id = $chat['participants'][0]['id'];

        //   try {
        //     $response2 = $fb->get('/' . $id . '/picture?redirect=false', $user->fb_page);
        //   } catch (Facebook\Exceptions\FacebookSDKException $e) {
        //     // dd($e->getMessage());
        //   }

        //   $chat['participants'][0]['picture'] = $response2;

        //   var_dump($response2);
        //   exit();

        //   $chats[] = $chat;
        // }

        return response()->view('admin/chats', [
            'chats' => $chats,
            'user' => $user,
        ]);
    }

    public function chat(LaravelFacebookSdk $fb, $id)
    {
        $page_id = 1338260872868488;

        $user = Auth::user();

        // Jeśli nie jesteś zalogowany do fb
        if (is_null($user->fb_page)) {
            return redirect('/admin/settings/facebook');
        }

        try {
            $response = $fb->get('/' . $id . '/messages?fields=message,attachments,from,shares{link}', $user->fb_page);
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            dd($e->getMessage());
        }

        $messages = $response->getGraphEdge();

        // return response($messages);

        return response()->view('admin/chat', [
            'id' => $id,
            'messages' => $messages,
            'fb_page' => $page_id,
            'user' => $user,
        ]);
    }
}
