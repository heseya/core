<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title')</title>
    <link href="https://fonts.googleapis.com/css?family=Jura:400,700&display=swap&subset=latin-ext" rel="stylesheet">
</head>
<body style="margin: 0; background-color: #ffffff;">
    <center>
        <table style="border-collapse: collapse; margin: 0; padding: 0; width: 100% !important; font-family: 'Jura', sans-serif; max-width: 420px" cellspacing="0" cellpadding="0" border="0" align="center"><tbody>
        <tr>
            <td height="20"></td>
        </tr>
        <tr>
            <td>
            <tr>
                <td align="center">
                <a href="{{ Config::get('app.store_url') }}" target="_blank">
                    <img src="https://depth.store/img/depth-b.svg" alt="DEPTH" style="border:none" height="64">
                </a>
                </td>
            </tr>
            <tr>
                <td height="14"></td>
            </tr>
            <tr>
                <td style="color: #000000; text-transform: uppercase; font-size: 15px; padding: 0 20px;" valign="middle" align="center">
                @yield('number')
                </td>
            </tr>
            </td>
        </tr>
        <tr>
            <td height="40"></td>
        </tr>
        <tr>
            <td align="left">
            <h2 style="color: #000000; margin: 0; text-transform: uppercase; font-size: 15px; padding: 0 20px;">
                @yield('greeting'),
            </h2>
            </td>
        </tr>
        <tr>
            <td height="20"></td>
        </tr>
        <tr>
            <td align="left">
            <h2 style="color:#000000; margin: 0; text-transform: uppercase; font-size: 14px; padding: 0 20px;">
                @yield('title')
            </h2>
            </td>
        </tr>

        @yield('content')

        <tr><td height="60"></td></tr>
        <tr>
            <td align="center">
            <p style="color: #969696; margin: 0; font-size: 14px; padding: 0 20px;">W razie czego pisz:</p>
            </td>
        </tr>
        <tr>
            <td align="center">
            <a href="mailto:{{ Config::get('mail.address') }}" style="color: #017173; text-decoration: none; font-weight: bold; font-size: 15px; padding: 0 20px;" target="_blank">
                {{ Config::get('mail.address') }}
            </a>
            </td>
        </tr>
        <tr>
            <td height="40"></td>
        </tr>
        <td>
            <tr>
            <td style="font-size: 12px; padding: 0 20px;" align="center">
                <a href="https://www.facebook.com/groups/depthtalk" style="color: #8d8d8d; text-decoration: none;" target="_blank">Depth&nbsp;Talk</a>
                -
                <a href="https://www.facebook.com/kupdepth" style="color: #8d8d8d; text-decoration: none;" target="_blank">Facebook</a>
                -
                <a href="https://www.instagram.com/kupdepth" style="color: #8d8d8d; text-decoration: none;" target="_blank">Instagram</a>
            </td>
            </tr>
        </td>
        <tr>
            <td height="10"></td>
        </tr>
        </tbody></table>
    </center>
</body>
