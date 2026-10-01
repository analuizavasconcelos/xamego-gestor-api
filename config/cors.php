'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout'],

'allowed_methods' => ['*'],

'allowed_origins' => [
    'https://xamego-gestor-web.vercel.app',
    'http://localhost:5173', 
],

'allowed_origins_patterns' => [],

'allowed_headers' => ['*'],

'exposed_headers' => [],

'max_age' => 0,

'supports_credentials' => true,