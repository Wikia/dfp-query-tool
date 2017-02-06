var CONF = {
		IO: {HOST: '0.0.0.0', PORT: 8080},
		EXPRESS: {HOST: 'localhost', PORT: 26300}
	},
	app = require('express')();


app.use(function(request, response, next) {
	response.header("Access-Control-Allow-Origin", "*");
	response.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
	next();
});


app.get('/emit/:id/:message', function (req, res) {
	io.sockets.emit(req.params.id, req.params.message);
	res.json('OK');
});

var server = app.listen(CONF.EXPRESS.PORT, CONF.EXPRESS.HOST);

var io = require('socket.io').listen(server);
