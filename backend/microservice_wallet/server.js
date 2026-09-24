// Optional Local Test Server (Only needed if testing locally)
const http = require('http');
const handler = require('./api/process-wallet');

const server = http.createServer((req, res) => {
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
        try {
            req.body = body ? JSON.parse(body) : {};
        } catch (e) {
            req.body = {};
        }

        // Mock json method on res
        res.json = (data) => {
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify(data));
        };
        res.status = (code) => {
            res.statusCode = code;
            return res;
        };

        handler(req, res);
    });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Local Wallet Microservice running on http://localhost:${PORT}`);
});
