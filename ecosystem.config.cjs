module.exports = {
    apps: [
        {
            name: 'ValiantShadeWeb',
            cwd: '/var/www/Valiant-Shade-Web',
            script: 'artisan',
            interpreter: 'php',
            args: 'serve --host=127.0.0.1 --port=9080 --no-reload',
            autorestart: true,
            max_restarts: 20,
            min_uptime: '10s',
        },
    ],
};
