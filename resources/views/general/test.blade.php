<head>
	
	<title>2.3 Colisiones</title>
	<script src="{{ asset('js/supplementary/jquery.min.js') }}"></script>
	<script type="text/javascript" src="{{ asset('js/three/three.js') }}"></script>
	<script type="text/javascript" src="{{ asset('js/three/MTLLoader.js') }}"></script>
	<script type="text/javascript" src="{{ asset('js/three/OBJLoader.js') }}"></script>

	<script type="text/javascript">

	var scene;
	var camera;
	var renderer;
	var controls;
	var objects = [];
	var clock;
	var deltaTime;	
	var keys = {};

	var rayCaster;
	var objetosConColision = [];

	var isWorldReady = [ false, false ];
	$(document).ready(function() {

		setupScene();

		rayCaster = new THREE.Raycaster();

		camera.rayos = [
			new THREE.Vector3(1, 0, 0),
			new THREE.Vector3(-1, 0, 0),
			new THREE.Vector3(0, 0, 1),
			new THREE.Vector3(0, 0, -1),
		];

		loadOBJWithMTL("assets/", "box.obj", "box.mtl", (object) => {
			object.position.z = -30;			

			var box2 = object.clone();
			box2.position.x = 30;

			var box3 = object.clone();
			box3.position.x = -30;


			var box4 = object.clone();
			box4.position.x = 0;
			box4.position.z = 30;

			var box5 = box4.clone();
			box5.position.x = 30;

			var box6 = box4.clone();
			box6.position.x = -30;

			var box7 = object.clone();		
			box7.position.z = 0;
			box7.position.x = 50;
			box7.rotation.y = THREE.Math.degToRad(90);

			var box8 = box7.clone();		
			box8.position.x = -50;
			box8.rotation.y = THREE.Math.degToRad(-90);


			scene.add(object);
			scene.add(box2);
			scene.add(box3);
			scene.add(box4);
			scene.add(box5);
			scene.add(box6);
			scene.add(box7);
			scene.add(box8);

			objetosConColision.push(object);
			objetosConColision.push(box2);
			objetosConColision.push(box3);
			objetosConColision.push(box4);
			objetosConColision.push(box5);
			objetosConColision.push(box6);
			objetosConColision.push(box7);
			objetosConColision.push(box8);
			

			isWorldReady[0] = true;
		});

		loadOBJWithMTL("assets/", "jetski.obj", "jetski.mtl", (object) => {
			object.position.z = -10;
			object.rotation.x = THREE.Math.degToRad(-90);

			scene.add(object);
			isWorldReady[1] = true;
		});

		render();

		document.addEventListener('keydown', onKeyDown);
		document.addEventListener('keyup', onKeyUp);		
	});

	function loadOBJWithMTL(path, objFile, mtlFile, onLoadCallback) {
		var mtlLoader = new THREE.MTLLoader();
		mtlLoader.setPath(path);
		mtlLoader.load(mtlFile, (materials) => {
			
			var objLoader = new THREE.OBJLoader();
			objLoader.setMaterials(materials);
			objLoader.setPath(path);
			objLoader.load(objFile, (object) => {
				onLoadCallback(object);
			});

		});
	}

	function onKeyDown(event) {
		keys[String.fromCharCode(event.keyCode)] = true;
	}
	function onKeyUp(event) {
		keys[String.fromCharCode(event.keyCode)] = false;
	}

	
	function render() {
		requestAnimationFrame(render);
		deltaTime = clock.getDelta();	

		var yaw = 0;
		var forward = 0;
		if (keys["A"]) {
			yaw = 5;
		} else if (keys["D"]) {
			yaw = -5;
		}
		if (keys["W"]) {
			forward = -20;
		} else if (keys["S"]) {
			forward = 20;
		}

		if (isWorldReady[0] && isWorldReady[1]) {

			for (var i = 0; i < camera.rayos.length; i++) {

				// "Lanzamos" el rayo
				// 1er Param: Desde donde lanzamos el rayo
				// 2do Param: Direccion del rayo

				rayCaster.set(camera.position, camera.rayos[i]);

				// Verificamos si hay colision

				// 1er Param: Objetos con los que evaluar si hay colision
				// 2do Param: Para detectar tambien colision con los hijos
				var colision = rayCaster.intersectObjects(objetosConColision, true);

				if (colision.length > 0 && colision[0].distance < 1) {
					// Si hay colision
					console.log("Ya estas colisionando!");
					forward = -4 * (forward);
				}

			}
			

			//if (camera.direction.x !== 0 || camera.direction.z !== 0){
			camera.rotation.y += yaw * deltaTime;
			camera.translateZ(forward * deltaTime);
			//}

		}
		
	
		renderer.render(scene, camera);
	}

	function setupScene() {		
		var visibleSize = { width: window.innerWidth, height: window.innerHeight};
		clock = new THREE.Clock();		
		scene = new THREE.Scene();
		camera = new THREE.PerspectiveCamera(75, visibleSize.width / visibleSize.height, 0.1, 100);
		camera.position.z = 2;
		camera.position.y = 5;

		renderer = new THREE.WebGLRenderer( {precision: "mediump" } );
		renderer.setClearColor(new THREE.Color(0, 0, 0));
		renderer.setPixelRatio(visibleSize.width / visibleSize.height);
		renderer.setSize(visibleSize.width, visibleSize.height);

		var ambientLight = new THREE.AmbientLight(new THREE.Color(1, 1, 1), 1.0);
		scene.add(ambientLight);

		var directionalLight = new THREE.DirectionalLight(new THREE.Color(1, 1, 0), 0.4);
		directionalLight.position.set(0, 0, 1);
		scene.add(directionalLight);

		var grid = new THREE.GridHelper(50, 10, 0xffffff, 0xffffff);
		grid.position.y = -1;
		scene.add(grid);

		$("#scene-section").append(renderer.domElement);
	}


	</script>
</head>

<body>

	<div id="scene-section"/>

</body>
</html>