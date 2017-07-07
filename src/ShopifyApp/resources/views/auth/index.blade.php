<!DOCTYPE html>
<html lang="en">
    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        <title>Shopify App — Installation</title>

        <style>
            html, body { padding: 0; margin: 0; }
            body {
                font-family: "ProximaNovaLight", "Helvetica Neue", Helvetica, Arial, sans-serif;
                background-color: #f2f7fa;
            }
            h1 {
                font-weight: 300;
                font-size: 40px;
                margin-bottom: 10px;
            }
            .subhead {
                font-size: 17px;
                line-height: 32px;
                font-weight: 300;
                color: #969A9C;
            }
            input {
                width: 300px;
                height: 50px;
                padding: 10px;
                border: 1px solid #479CCf;
                color: #575757;
                background-color: #ffffff;
                box-sizing: border-box;
                border-radius: 4px 0 0 4px;
                font-size: 18px;
                float: left;
            }
            button {
                color: #ffffff;
                background-color: #3793cb;
                width: 100px;
                height: 50px;
                padding: 10px 20px 10px 20px;
                box-sizing: border-box;
                border: none;
                text-shadow: 0 1px 0 #3188bc;
                font-size: 18px;
                cursor: pointer;
                border-radius: 0 4px 4px 0;
                float: right;
            }
            button:hover {
                background-color: #479CCf;
            }
            form {
                display: block;
            }
            .container {
                text-align: center;
                margin-top: 100px;
                padding: 20px;
            }
            .container__form {
                width: 400px;
                margin: auto;
            }
        </style>
    </head>
    <body>
        <main class="container" role="main">
            <header>
                <h1>{{ config('shopify-app.app_name') }}</h1>
                <p class="subhead">
                    <label for="shop">Enter your shop domain to log in or install this app.</label>
                </p>
            </header>

            <div class="container__form">
                <form class="form-horizontal" method="POST" action="{{ route('authenticate') }}">
                    {{ csrf_field() }}
                    <input type="text" name="shopify_domain" id="shop" placeholder="example.myshopify.com">
                    <button type="submit">Install</button>
                </form>
            </div>
        </main>
    </body>
</html>