<!DOCTYPE html>
<html lang="en">

<head>
    <title>Kitch 'n Rush</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800,900" rel="stylesheet">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <link rel="stylesheet" href="{{ asset('css/supplementary/animate.css') }}">

    <link rel="stylesheet" href="{{ asset('css/supplementary/owl.carousel.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/supplementary/owl.theme.default.min.css') }}" type="text/css">
    <link rel="stylesheet" href="{{ asset('css/supplementary/magnific-popup.css') }}">

    <link rel="stylesheet" href="{{ asset('css/supplementary/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/supplementary/jquery.timepicker.css') }}">

    <link rel="stylesheet" href="{{ asset('css/supplementary/flaticon.css') }}">
    <link rel="stylesheet" href="{{ asset('css/supplementary/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <script src="{{ asset('js/supplementary/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/app.js') }}"></script>

    <script src="https://pagecdn.io/lib/three/110/three.min.js" crossorigin="anonymous"></script>
    <!--script type="text/javascript" src="{{ asset('js/three/three.js') }}"></script-->
    <script type="text/javascript" src="{{ asset('js/three/MTLLoader.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/three/OBJLoader.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/three/GLTFLoader.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/three/OrbitControls.js') }}"></script>

    @livewireStyles
    @livewireScripts

    <script type="application/x-glsl" id="sky-vertex">
        varying vec2 vUV;

      void main() {  
        vUV = uv;
        vec4 pos = vec4(position, 1.0);
        gl_Position = projectionMatrix * modelViewMatrix * pos;
      }
    </script>

    <script type="application/x-glsl" id="sky-fragment">
        uniform sampler2D texture;  
      varying vec2 vUV;

      void main() {  
        vec4 sample = texture2D(texture, vUV);
        gl_FragColor = vec4(sample.xyz, sample.w);
      }
    </script>

    <script type="module">
    /////////////////////////////////////////////////
    //Universal variables.

    //Default
    var scene; //Object that draws the scene.
    var camera; //Object that sees the scene.
    var renderer;
    var controls;
    var objects = [];
    var clock;
    var timer;
    var deltaTime;
    var keys = {};

    //Custom
    var mesh; //EXPERIMENTAL. Trying to build an universal mesh buffer. 

    //Colisions
    var rayCaster;
    var objetosConColision = [];

    //Loaders
    var GLTFLoader;
    var audioLoader; //EXPERIMENTAL. Trying to have an audio player.
    var cubeLoader;
    var textureLoader;

    //Game variables


    //Flags
    var isWorldReady = [false, false, false]; //Checks if everything is loaded correctly.
    var isPlayerReady = [false, false];

    //URL bundles
    var urls = [
        'assets/posx.jpg', 'assets/negx.jpg',
        'assets/posy.jpg', 'assets/negy.jpg',
        'assets/posz.jpg', 'assets/negz.jpg'
    ];

    //Materials
    var glslMaterial;

    //Gamepad
    var gamepad;

    //Audio
    var listener;
    var sound;

    //Particles
    var cloudParticles = [];

    //Animation
    var mixer;
    var mixer2;

    /////////////////////////////////////////////////
    //Obligatory starter shit.

    //Set up anything important for the program.
    function setupScene() {
        ////////////////////
        //Basic settings

        //My loaders
        GLTFLoader = new THREE.GLTFLoader();
        audioLoader = new THREE.AudioLoader();
        cubeLoader = new THREE.CubeTextureLoader();
        textureLoader = new THREE.TextureLoader();

        //Set up canvas and camera
        var visibleSize = {
            width: window.innerWidth,
            height: window.innerHeight
        };
        clock = new THREE.Clock();
        timer = new THREE.Clock();
        scene = new THREE.Scene();
        camera = new THREE.PerspectiveCamera(75, visibleSize.width / visibleSize.height, 0.1, 2000);

        //Audio
        listener = new THREE.AudioListener();
        sound = new THREE.PositionalAudio(listener);
        camera.add(listener);

        //HDRI
        //scene.background = cubeLoader.load(urls);

        //Initial camera positions.
        camera.position.z = 5;
        camera.position.y = 20;
        camera.rotation.x = THREE.Math.degToRad(-70);

        ////////////////////
        //Renderer settings

        renderer = new THREE.WebGLRenderer({
            precision: "mediump"
        });
        //renderer.setClearColor(new THREE.Color(0, 0, 0));
        renderer.toneMapping = THREE.ReinhardToneMapping;
        renderer.toneMappingExposure = 1.3;
        renderer.shadowMap.enabled = true;
        renderer.setPixelRatio(visibleSize.width / visibleSize.height);
        renderer.setSize(visibleSize.width, visibleSize.height);

        ////////////////////
        //Lighting

        //variables
        var ambientLightIntensity = 0.5;

        //Setup
        var ambientLight = new THREE.AmbientLight(new THREE.Color(1, 1, 0.9), ambientLightIntensity);
        var directionalLight = new THREE.DirectionalLight(new THREE.Color(1, 1, 0), 0.3);
        directionalLight.castshadow = true;
        directionalLight.position.set(0, 0, 1);
        var spotLight = new THREE.SpotLight(0xffa95c, 2);
        spotLight.position.set(0, 15, 0);
        spotLight.castshadow = true;
        var hemiLight = new THREE.HemisphereLight(0xffeeb1, 0x080820, 4);

        //Adding
        scene.add(spotLight);
        scene.add(ambientLight);
        scene.add(directionalLight);
        scene.add(hemiLight);

        ////////////////////
        //DEBUG: Grid

        //Adds the grid
        var grid = new THREE.GridHelper(50, 10, 0xffffff, 0xffffff);
        grid.position.y = -1;
        scene.add(grid);

        ////////////////////
        //Materials
        var geometry = new THREE.SphereGeometry(100, 60, 40);

        var uniforms = {
            texture: {
                type: 't',
                value: textureLoader.load('assets/posy.jpg')
            }
        };

        var material = new THREE.ShaderMaterial({
            uniforms: uniforms,
            vertexShader: document.getElementById('sky-vertex').textContent,
            fragmentShader: document.getElementById('sky-fragment').textContent
        });
        material.side = THREE.BackSide;
        var skyBox = new THREE.Mesh(geometry, material);

        skyBox.scale.set(10, 10, 10);
        skyBox.eulerOrder = 'XZY';
        skyBox.renderDepth = 1000.0;
        scene.add(skyBox);

        ////////////////////



        //Delect the ID of the to-be canvas tag
        $("#splash-canvas").append(renderer.domElement);

        controls = new THREE.OrbitControls(camera, renderer.domElement);


        window.addEventListener('resize', onWindowResize, false);
    }

    /////////////////////////////////////////////////
    //Key inputs.

    function onKeyDown(event) {
        keys[String.fromCharCode(event.keyCode)] = true;
    }

    function onKeyUp(event) {
        keys[String.fromCharCode(event.keyCode)] = false;
    }

    /////////////////////////////////////////////////

    $(document).ready(function() {

        $("#play-btn").click(function() {

            $("#play-registry").toggle("fast", "swing", function() {
                $("#select-stage").toggle();
            });

        });

        $("#ready-p2-btn").click(function() {

            $("#play-registry").toggle("fast", "swing", function() {
                $("#select-stage").toggle();
            });

        });

        $("#okay-btn").click(function() {
            $("#initial-info").toggle();
            $("#select-stage").toggle();
            $("#username").toggle();

            $("#gameplay-instructions").toggle();

            isPlayerReady[0] = true;
        });

        $(".course-category").click(function() {

            $(".course-category").removeClass('selected');
            $(this).toggleClass('selected');

            if (sound.isPlaying)
                sound.stop();

            switch ($(this).attr('id')) {
                case "cc_murrica":

                    audioLoader.load('assets/turkey.mp3', function(buffer) {

                        sound.setBuffer(buffer);
                        sound.setLoop(true);
                        sound.setVolume(1);
                        sound.play();

                    });
                    break;

                case "cc_italy":

                    audioLoader.load('assets/funi.mp3', function(buffer) {

                        sound.setBuffer(buffer);
                        sound.setLoop(true);
                        sound.setVolume(1);
                        sound.play();

                    });
                    break;

                case "cc_germany":

                    audioLoader.load('assets/yodel.mp3', function(buffer) {

                        sound.setBuffer(buffer);
                        sound.setLoop(true);
                        sound.setVolume(1);
                        sound.play();

                    });
                    break;

                default:
                    // code block
            };

        });

        setupScene();

        rayCaster = new THREE.Raycaster();

        camera.rayos = [
            new THREE.Vector3(1, 0, 0),
            new THREE.Vector3(-1, 0, 0),
            new THREE.Vector3(0, 0, 1),
            new THREE.Vector3(0, 0, -1),
        ];


        /////////////////////////////////////////////////
        //Loading.

        //Load your shit here.

        var character;
        var character2;

        var box;

        
        GLTFLoader.load('assets/boxes.glb', function(gltf) {

          box = gltf.scene;
          //box.scale.set(3.5, 3.5, 3.5);
          //box.rotation.y = THREE.Math.degToRad(180);

          box.name = "box";

          scene.add(box);

          objetosConColision.push(box);

        }, undefined, function(error) {
        console.error(error);
        });
        
        

        GLTFLoader.load('assets/chef.glb', function(gltf) {

            character = gltf.scene;

            character.rayos = [
            new THREE.Vector3(1, 0, 0),
            new THREE.Vector3(-1, 0, 0),
            new THREE.Vector3(0, 0, 1),
            new THREE.Vector3(0, 0, -1)
        ];

            mixer = new THREE.AnimationMixer(character);
            mixer.clipAction(gltf.animations[0]).play();

            character.name = "character";

            scene.add(character);

            textureLoader.load("assets/cloud.png", function(texture) {
                let cloudGeo = new THREE.PlaneBufferGeometry(1, 1);
                let cloudMaterial = new THREE.MeshLambertMaterial({
                    map: texture,
                    transparent: true
                });


                for (let p = 0; p < 50; p++) {
                    let cloud = new THREE.Mesh(cloudGeo, cloudMaterial);
                    cloud.scale.set(1, 1, 1);
                    cloud.position.set(
                        Math.random() * 2 - 1,
                        2,
                        Math.random() * 2 - 3
                    );
                    cloud.rotation.x = 1.16;
                    cloud.rotation.y = -0.12;
                    cloud.rotation.x = Math.random() * 2 * Math.PI;
                    cloud.material.opacity = 0.55;
                    cloudParticles.push(cloud);
                    scene.add(cloud);
                    character.add(cloud);
                }
            });




            isWorldReady[0] = true;
        }, undefined, function(error) {
            console.error(error);
        });

        GLTFLoader.load('assets/chef2.glb', function(gltf) {

            character2 = gltf.scene;

            mixer2 = new THREE.AnimationMixer(character2);
            mixer2.clipAction(gltf.animations[0]).play();

            character2.name = "character2";

            scene.add(character2);

            isWorldReady[1] = true;
        }, undefined, function(error) {
            console.error(error);
        });


        GLTFLoader.load('assets/kitchenComplete2.glb', function(gltf) {

            //Transformations
            //gltf.scene.scale.set(3.5, 3.5, 3);
            //gltf.scene.rotation.y = THREE.Math.degToRad(180);

            //Check it out in console
            //console.log(gltf);

            mesh = gltf.scene;
            //console.log(mesh.children[0]);
            mesh.children[0].material = new THREE.MeshLambertMaterial();

            //Add the name
            mesh.name = "scenery";

            //Add to the scene.
            mesh.traverse(n => {
                if (n.isMesh) {
                    n.castShadow = true;
                    n.receiveShadow = true;
                }
            });



            //character2 = character.clone();
            // scene.add(character2);
            mesh.add(character);
            mesh.add(character2);

            scene.add(mesh);
            //character2.scale.set(0.01,0.01,0.01);
            //character2.position.set(0, 2, 0);
            character2.scale.set(0.7, 0.8, 0.7);
            character2.position.set(0, 0, 1.8);
            character.scale.set(0.7*2, 0.8*2, 0.7*2);
            //character.rotation.y = THREE.Math.degToRad(180);
            character.position.set(0, 0.3, 2.8);

            //objetosConColision.push(mesh);
            //Check as ready.

            isWorldReady[2] = true;
        }, undefined, function(error) {
            console.error(error);
        });


        render();

        /////////////////////////////////////////////////
        //Event listeners

        //Key inputs
        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('keyup', onKeyUp);

        /////////////////////////////////////////////////
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

    /////////////////////////////////////////////////
    //Renderer loop

    function render() {

        //Loop
        requestAnimationFrame(render);

        //Start the clock
        deltaTime = clock.getDelta();

        //Variables
        var movementIndex = 3;
        var isMoving = [false, false];
        var isAction = [false, false];
        //var iscollisioning = [false, false];
        var yaw = [0, 0];
        var forward = [0, 0];

        var character;
        var character2;
        var scenery;
        var box;

        var slow = clock.getElapsedTime() * 4;
        var toggle = true;




        /////////////////////////////////////////////////

        //Start if everything is ready.
        if (isWorldReady[0] && isWorldReady[1] && isWorldReady[2]) {

            //Get by names
            character = scene.getObjectByName("character");
            character2 = scene.getObjectByName("character2");
            scenery = scene.getObjectByName("scenery");
            box = scene.getObjectByName("box");



            /////////////////////////////////////////////////
            //Controls

            //Add the keyboard presses.
            if (keys["A"]) {
                //window.Livewire.emit('increment');
                yaw[0] = movementIndex;


                isMoving[0] = true;
            } else if (keys["D"]) {
                yaw[0] = -movementIndex;

                isMoving[0] = true;
            }

            if (keys["W"]) {
                //character.visible = !character.visible;
                //forward = -movementIndex;

                isMoving[0] = true;
            } else if (keys["S"]) {
                //forward = movementIndex;

                isMoving[0] = true;
            }

            if (keys["E"]) {

                isAction[0] = true;
            }

            if (keys["R"]) {
                $('#results-modal').modal('toggle');
                $('#results-modal').modal('show');
                $('#results-modal').modal('hide');

            }

            if (keys["P"]) {
                $('#pause-modal').modal('toggle');
                $('#pause-modal').modal('show');
                $('#pause-modal').modal('hide');
            }

            if (keys["H"]) {
                $('#highscores-modal').modal('toggle');
                $('#highscores-modal').modal('show');
                $('#highscores-modal').modal('hide');
            }

            gamepad = navigator.getGamepads ? navigator.getGamepads() : (navigator.webkitGetGamepads ? navigator
                .webkitGetGamepads : []);

            if (gamepad.length > 0) {
                gamepad = gamepad[0];
            }

            if (gamepad) {
                if (gamepad.connected) {

                    if (gamepad.axes[0] > .5) //LStick right
                    {
                        yaw[1] = -movementIndex;

                        isMoving[1] = true;
                    }

                    if (gamepad.axes[1] > .5) //LStick down
                    {
                        //forward = movementIndex;

                        isMoving[1] = true;
                    }

                    if (gamepad.axes[0] < -.5) //LStick left
                    {
                        yaw[1] = movementIndex;

                        isMoving[1] = true;
                    }

                    if (gamepad.axes[1] < -.5) //LStick up
                    {
                        //forward = -movementIndex;

                        isMoving[1] = true;
                    }

                    if (gamepad.buttons[0].pressed) //A
                    {}

                    if (gamepad.buttons[0].pressed) //B
                    {}

                    if (gamepad.buttons[0].pressed) //X
                    {}

                    if (gamepad.buttons[0].pressed) //Y
                    {}

                    if (gamepad.buttons[0].pressed) //RTrigger
                    {}

                    if (gamepad.buttons[0].pressed) //LTrigger
                    {}

                }
            }

            



            if (isPlayerReady[0] && isPlayerReady[1]) {
                    scenery.rotation.y += THREE.Math.degToRad(0.005 + 0.001 * (Math.floor(timer.getElapsedTime()) / 6));


                    //Timer
                    var minutes = Math.floor(Math.floor(timer.getElapsedTime()) / 60);
                    var seconds = Math.floor(timer.getElapsedTime()) - minutes * 60;
                    var secondsString = "";
                    if (seconds <= 10) {
                        secondsString = "0" + seconds.toString();
                    } else {
                        secondsString = seconds.toString();
                    }

                    sound.setPlaybackRate(1 + 0.1 * (Math.floor(timer.getElapsedTime()) / 20));
                    $("#counter").text(minutes + ":" + secondsString);
                }

            //if (camera.direction.x !== 0 || camera.direction.z !== 0){
            //camera.rotation.y += yaw * deltaTime;

            //camera.translateZ(forward * deltaTime);
            //character.rotation.y += yaw * deltaTime;

            if (isAction[0]) {
                //Particles
                cloudParticles.forEach(p => {
                    p.visible = true;
                    p.rotation.z -= 0.1;
                    p.rotation.x -= 0.02;
                    if (toggle) {
                        p.scale.set(Math.sin(slow) / 2 + 0.5, Math.sin(slow) / 2 + 0.5, Math.sin(slow) / 2 +
                            0.5);
                        toggle = false;
                    } else {
                        p.scale.set(Math.sin(-slow) / 2 + 0.5, Math.sin(-slow) / 2 + 0.5, Math.sin(-slow) / 2 +
                            0.5);
                        toggle = true;
                    }

                });
            } else {

                cloudParticles.forEach(p => {
                    p.visible = false;


                });

            }


            //Animation updates
            if (isMoving[0])
                mixer.update(deltaTime);

            if (isMoving[1])
                mixer2.update(deltaTime);
            

            character.translateX(yaw[0] * deltaTime);

            box.visible = true;
            
            for (var i = 0; i < character.rayos.length; i++) {

              // "Lanzamos" el rayo
              // 1er Param: Desde donde lanzamos el rayo
              // 2do Param: Direccion del rayo

              rayCaster.set(character.position, character.rayos[i]);

              // Verificamos si hay colision

              // 1er Param: Objetos con los que evaluar si hay colision
              // 2do Param: Para detectar tambien colision con los hijos
              var colision = rayCaster.intersectObjects(objetosConColision, true);

              if (colision.length > 0 && colision[0].distance < 1) {
                  // Si hay colision
                  console.log("Ya estas colisionando!");
              
                  if(character.position.x <= -12 || character.position.x >= 7)
                    character.translateX(-yaw[0] * deltaTime);
              }
            }
            box.visible = false;




            character2.translateX(yaw[1] * deltaTime);
            //}

        }


        renderer.render(scene, camera);
    }

    /////////////////////////////////////////////////
    //Adittional functions

    //Resize canvas in real time
    function onWindowResize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }

    window.addEventListener("gamepadconnected", function(e) {
        console.log("Gamepad connected at index %d: %s. %d buttons, %d axes.",
            e.gamepad.index, e.gamepad.id,
            e.gamepad.buttons.length, e.gamepad.axes.length);
        isPlayerReady[1] = true;
    });

    /////////////////////////////////////////////////
    </script>



</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.html"><span>Kitch 'n </span>Rush</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav"
                aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="oi oi-menu"></span> Menu
            </button>

            <div class="collapse navbar-collapse" id="ftco-nav">
                <ul class="navbar-nav ml-auto">
                    <li class="p_logged nav-item">
                        <p id="counter" class="nav-link"></p>
                    </li>

                    @auth
                    <li id="username" style="display: none;" class="p_logged nav-item"><a href="instructor.html"
                            class="nav-link">{{ Auth::user()->name }}</a></li>
                    @endauth

                    @guest
                    <li id="username" style="display: none;" class="p_guest nav-item"><a href="instructor.html"
                            class="nav-link">Guest</a></li>
                    @endguest

                    <!--li class="nav-item"><a href="blog.html" class="nav-link">Options</a></li-->
                    <li class="nav-item"><a href="#" data-toggle="modal" data-target="#help-modal"
                            class="nav-link">Help</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- END nav -->

    <div class="hero-wrap js-fullheight">

        <div class=" overlay" id="splash-canvas"></div>
        <div class="container" id="initial-info">
            <div class="row no-gutters slider-text js-fullheight align-items-center" data-scrollax-parent="true">
                <div class="col-md-7 ftco-animate">
                    <span class="subheading">Welcome to Kitch 'n Rush!</span>
                    <h1 class="mb-4">Online Speedrun cooking simulator.</h1>
                    <p class="caps">Start playing by creating an account or play as a guest. Select one mode ad voila!
                        Fun never ends.</p>
                    <!--p class="mb-0"><a href="#" class="btn btn-primary">Our Course</a> <a href="#" class="btn btn-white">Learn More</a></p-->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="highscores-modal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">

                    <h4 class="modal-title">highscores</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center pb-4">
                        <div class="col-md-12 heading-section text-center ftco-animate">
                            <span class="subheading">Congratulations!</span>
                            <h2 class="mb-4">You Won!</h2>

                            <livewire:highscore />

                        </div>
                    </div>
                    <p>With: 0.00 seconds to spare.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default purple" data-dismiss="modal">Neat!</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="results-modal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">

                    <h4 class="modal-title">Results</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center pb-4">
                        <div class="col-md-12 heading-section text-center ftco-animate">
                            <span class="subheading">Congratulations!</span>
                            <h2 class="mb-4">You Won!</h2>
                        </div>
                    </div>
                    <p>With: 0.00 seconds to spare.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default purple" data-dismiss="modal">Neat!</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="help-modal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">

                    <h4 class="modal-title">Controls</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center pb-4">
                        <div class="col-md-12 heading-section text-center ftco-animate">
                            <p>WASD: Move around.</p>
                            <p>Mouse: Move hand.</p>
                            <p>Right Click: Grab.</p>
                            <p>Left Click: Let go.</p>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default purple" data-dismiss="modal">Got it!</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="pause-modal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">

                    <h4 class="modal-title">Pause</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center pb-4">
                        <div class="col-md-12 heading-section text-center ftco-animate">

                            <h3 class="mb-4">On pause...</h3>
                            <span class="subheading">Remember that this is an online game, so time is still
                                running!</span>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default purple" data-dismiss="modal">Go Back!</button>
                </div>
            </div>

        </div>
    </div>

    <section class="ftco-section ftco-no-pb ftco-no-pt" id="play-registry">
        <div class="container">
            <div class="row">
                <div class="col-md-7"></div>
                <div class="col-md-5 order-md-last">
                    <div class="login-wrap p-4 p-md-5">
                        <h3>Start playing</h3>
                        <hr>
                        <form action="#" class="signup-form">
                            <div class="">
                                <p>As a guest...</p>

                            </div>
                            <div class="">
                                <label class="label" for="name">Alias</label>
                                <input type="text" class="form-control" placeholder="xXx_PussySlayer_xXx">
                            </div>
                            <div class="form-group d-flex justify-content-end mt-4">
                                <button id="play-btn" type="button" class="btn purple submit fill">Play!</button>
                            </div>

                            <div class="form-group">
                                <p>Or use an account...</p>

                            </div>
                            <!--div class="form-group">
                    <label class="label" for="email">Email Address</label>
                    <input type="text" class="form-control" placeholder="johndoe@gmail.com">
                </div>
                <div class="form-group">
                 <label class="label" for="password">Password</label>
                 <input id="password-field" type="password" class="form-control" placeholder="Password">
             </div>
             <div class="form-group">
                 <label class="label" for="password">Confirm Password</label>
                 <input id="password-field" type="password" class="form-control" placeholder="Confirm Password">
             </div-->



                            <a href="{{ route('login-facebook')}}" class="btn btn-primary fill submit"><span
                                    class="fa fa-facebook"></span> Sign In</a>

                        </form>
                        <p class="text-center">Already have an account? <a href="#signin">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section ftco-no-pb ftco-no-pt" style="display: none;" id="gameplay-instructions">
        <div class="container gameplay">
            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4 order-md-last">
                    <div class="login-wrap gameplay p-4 p-md-5">
                        <h3>Shnitzel</h3>
                        <hr>
                        <form action="#" class="signup-form">
                            <div class="">
                                <p>1- Sandiw ☑☑</p>
                                <p>1- Sandiw</p>
                                <p>1- Sandiw</p>
                                <p>1- Sandiw</p>
                                <p>1- Sandiw</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="ftco-section ftco-no-pb ftco-no-pt" style="display: none;" id="select-stage">
        <div class="container">
            <div class="row">
                <div class="col-md-7"></div>
                <div class="col-md-5 order-md-last">
                    <div class="login-wrap p-4 p-md-5">
                        <h3>Select Dish</h3>
                        <hr>
                        <form action="#" class="signup-form">
                            <div class="form-group">
                                <p>Nationality</p>

                            </div>

                            <!--div class="row justify-content-center pb-4">
          <div class="col-md-12 heading-section text-center ftco-animate">
          	<span class="subheading">Start Learning Today</span>
            <h2 class="mb-4">Browse Online Course Category</h2>
        </div>
    </div-->

                            <div class="row justify-content-center">
                                <div class="col-md-4 col-lg-4">
                                    <a href="#" id="cc_germany"
                                        class="course-category img d-flex align-items-center justify-content-center"
                                        style="background-image: url({{ asset('images/schnitzel.jpg') }});">
                                        <div class="text w-100 text-center">
                                            <h3>Germany</h3>
                                            <span>Schnitzel</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-4 col-lg-4">
                                    <a href="#" id="cc_murrica"
                                        class="course-category img d-flex align-items-center justify-content-center"
                                        style="background-image: url({{ asset('images/pancakes.jpg') }});">
                                        <div class="text w-100 text-center">
                                            <h3>'Murrica</h3>
                                            <span>Pancakes</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-4 col-lg-4">
                                    <a href="#" id="cc_italy"
                                        class="course-category img d-flex align-items-center justify-content-center"
                                        style="background-image: url({{ asset('images/ravioli.jpg') }});">
                                        <div class="text w-100 text-center">
                                            <h3>Italy</h3>
                                            <span>Ravioli</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="form-group d-flex justify-content-end mt-4">
                                <button id="okay-btn" type="button" class="btn purple submit fill">Okay!</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--section class="ftco-section">
   <div class="container">
      <div class="row justify-content-center pb-4">
          <div class="col-md-12 heading-section text-center ftco-animate">
          	<span class="subheading">Start Learning Today</span>
            <h2 class="mb-4">Browse Online Course Category</h2>
        </div>
    </div>
    <div class="row justify-content-center">
     <div class="col-md-3 col-lg-2">
        <a href="#" class="course-category img d-flex align-items-center justify-content-center" style="background-image: url({{ asset('images/work_1.jpg') }});">
           <div class="text w-100 text-center">
              <h3>IT &amp; Software</h3>
              <span>100 course</span>
          </div>
      </a>
  </div>
  <div class="col-md-3 col-lg-2">
    <a href="#" class="course-category img d-flex align-items-center justify-content-center" style="background-image: url({{ asset('images/work_9.jpg') }});">
       <div class="text w-100 text-center">
          <h3>Music</h3>
          <span>100 course</span>
      </div>
  </a>
</div>
<div class="col-md-3 col-lg-2">
    <a href="#" class="course-category img d-flex align-items-center justify-content-center" style="background-image: url(images/work-3.jpg);">
       <div class="text w-100 text-center">
          <h3>Photography</h3>
          <span>100 course</span>
      </div>
  </a>
</div>
<div class="col-md-3 col-lg-2">
    <a href="#" class="course-category img d-flex align-items-center justify-content-center" style="background-image: url(images/work-5.jpg);">
       <div class="text w-100 text-center">
          <h3>Marketing</h3>
          <span>100 course</span>
      </div>
  </a>
</div>
<div class="col-md-3 col-lg-2">
    <a href="#" class="course-category img d-flex align-items-center justify-content-center" style="background-image: url(images/work-8.jpg);">
       <div class="text w-100 text-center">
          <h3>Health</h3>
          <span>100 course</span>
      </div>
  </a>
</div>
<div class="col-md-3 col-lg-2">
    <a href="#" class="course-category img d-flex align-items-center justify-content-center" style="background-image: url(images/work-6.jpg);">
       <span class="text w-100 text-center">
          <h3>Audio Video</h3>
          <span>100 course</span>
      </span>
  </a>
</div>
<div class="col-md-12 text-center mt-5">
    <a href="#" class="btn btn-secondary">See All Courses</a>
</div>
</div>
</div>
</section>

<section class="ftco-section bg-light">
   <div class="container">
      <div class="row justify-content-center pb-4">
          <div class="col-md-12 heading-section text-center ftco-animate">
          	<span class="subheading">Start Learning Today</span>
            <h2 class="mb-4">Pick Your Course</h2>
        </div>
    </div>
    <div class="row">
       <div class="col-md-4 ftco-animate">
          <div class="project-wrap">
             <a href="#" class="img" style="background-image: url(images/work-1.jpg);">
                <span class="price">Software</span>
            </a>
            <div class="text p-4">
                <h3><a href="#">Design for the web with adobe photoshop</a></h3>
                <p class="advisor">Advisor <span>Tony Garret</span></p>
                <ul class="d-flex justify-content-between">
                   <li><span class="flaticon-shower"></span>2300</li>
                   <li class="price">$199</li>
               </ul>
           </div>
       </div>
   </div>
   <div class="col-md-4 ftco-animate">
      <div class="project-wrap">
         <a href="#" class="img" style="background-image: url(images/work-2.jpg);">
            <span class="price">Software</span>
        </a>
        <div class="text p-4">
            <h3><a href="#">Design for the web with adobe photoshop</a></h3>
            <p class="advisor">Advisor <span>Tony Garret</span></p>
            <ul class="d-flex justify-content-between">
               <li><span class="flaticon-shower"></span>2300</li>
               <li class="price">$199</li>
           </ul>
       </div>
   </div>
</div>
<div class="col-md-4 ftco-animate">
  <div class="project-wrap">
     <a href="#" class="img" style="background-image: url(images/work-3.jpg);">
        <span class="price">Software</span>
    </a>
    <div class="text p-4">
        <h3><a href="#">Design for the web with adobe photoshop</a></h3>
        <p class="advisor">Advisor <span>Tony Garret</span></p>
        <ul class="d-flex justify-content-between">
           <li><span class="flaticon-shower"></span>2300</li>
           <li class="price">$199</li>
       </ul>
   </div>
</div>
</div>

<div class="col-md-4 ftco-animate">
  <div class="project-wrap">
     <a href="#" class="img" style="background-image: url(images/work-4.jpg);">
        <span class="price">Software</span>
    </a>
    <div class="text p-4">
        <h3><a href="#">Design for the web with adobe photoshop</a></h3>
        <p class="advisor">Advisor <span>Tony Garret</span></p>
        <ul class="d-flex justify-content-between">
           <li><span class="flaticon-shower"></span>2300</li>
           <li class="price">$199</li>
       </ul>
   </div>
</div>
</div>
<div class="col-md-4 ftco-animate">
  <div class="project-wrap">
     <a href="#" class="img" style="background-image: url(images/work-5.jpg);">
        <span class="price">Software</span>
    </a>
    <div class="text p-4">
        <h3><a href="#">Design for the web with adobe photoshop</a></h3>
        <p class="advisor">Advisor <span>Tony Garret</span></p>
        <ul class="d-flex justify-content-between">
           <li><span class="flaticon-shower"></span>2300</li>
           <li class="price">$199</li>
       </ul>
   </div>
</div>
</div>
<div class="col-md-4 ftco-animate">
  <div class="project-wrap">
     <a href="#" class="img" style="background-image: url(images/work-6.jpg);">
        <span class="price">Software</span>
    </a>
    <div class="text p-4">
        <h3><a href="#">Design for the web with adobe photoshop</a></h3>
        <p class="advisor">Advisor <span>Tony Garret</span></p>
        <ul class="d-flex justify-content-between">
           <li><span class="flaticon-shower"></span>2300</li>
           <li class="price">$199</li>
       </ul>
   </div>
</div>
</div>
</div>
</div>
</section>

<section class="ftco-section ftco-counter img" id="section-counter" style="background-image: url(images/bg_4.jpg);">
 <div class="overlay"></div>
 <div class="container">
    <div class="row">
       <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
         <div class="block-18 d-flex align-items-center">
            <div class="icon"><span class="flaticon-online"></span></div>
            <div class="text">
             <strong class="number" data-number="400">0</strong>
             <span>Online Courses</span>
         </div>
     </div>
 </div>
 <div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
     <div class="block-18 d-flex align-items-center">
        <div class="icon"><span class="flaticon-graduated"></span></div>
        <div class="text">
         <strong class="number" data-number="4500">0</strong>
         <span>Students Enrolled</span>
     </div>
 </div>
</div>
<div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
 <div class="block-18 d-flex align-items-center">
    <div class="icon"><span class="flaticon-instructor"></span></div>
    <div class="text">
     <strong class="number" data-number="1200">0</strong>
     <span>Experts Instructors</span>
 </div>
</div>
</div>
<div class="col-md-3 d-flex justify-content-center counter-wrap ftco-animate">
 <div class="block-18 d-flex align-items-center">
    <div class="icon"><span class="flaticon-tools"></span></div>
    <div class="text">
     <strong class="number" data-number="300">0</strong>
     <span>Hours Content</span>
 </div>
</div>
</div>
</div>
</div>
</section>

<section class="ftco-section ftco-about img">
   <div class="container">
      <div class="row d-flex">
         <div class="col-md-12 about-intro">
            <div class="row">
               <div class="col-md-6 d-flex">
                  <div class="d-flex about-wrap">
                     <div class="img d-flex align-items-center justify-content-center" style="background-image:url(images/about-1.jpg);">
                     </div>
                     <div class="img-2 d-flex align-items-center justify-content-center" style="background-image:url(images/about.jpg);">
                     </div>
                 </div>
             </div>
             <div class="col-md-6 pl-md-5 py-5">
              <div class="row justify-content-start pb-3">
                  <div class="col-md-12 heading-section ftco-animate">
                     <span class="subheading">Enhanced Your Skills</span>
                     <h2 class="mb-4">Learn Anything You Want Today</h2>
                     <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean. A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                     <p><a href="#" class="btn btn-primary">Get in touch with us</a></p>
                 </div>
             </div>
         </div>
     </div>
 </div>
</div>
</div>
</section>


<section class="ftco-section testimony-section bg-light">
   <div class="overlay" style="background-image: url(images/bg_2.jpg);"></div>
   <div class="container">
    <div class="row pb-4">
      <div class="col-md-7 heading-section ftco-animate">
         <span class="subheading">Testimonial</span>
         <h2 class="mb-4">What Are Students Says</h2>
     </div>
 </div>
</div>
<div class="container container-2">
    <div class="row ftco-animate">
      <div class="col-md-12">
        <div class="carousel-testimony owl-carousel">
          <div class="item">
            <div class="testimony-wrap py-4">
              <div class="text">
                 <p class="star">
                    <span class="fa fa-star"></span>
                    <span class="fa fa-star"></span>
                    <span class="fa fa-star"></span>
                    <span class="fa fa-star"></span>
                    <span class="fa fa-star"></span>
                </p>
                <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
                <div class="d-flex align-items-center">
                   <div class="user-img" style="background-image: url(images/person_1.jpg)"></div>
                   <div class="pl-3">
                      <p class="name">Roger Scott</p>
                      <span class="position">Marketing Manager</span>
                  </div>
              </div>
          </div>
      </div>
  </div>
  <div class="item">
    <div class="testimony-wrap py-4">
      <div class="text">
         <p class="star">
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
        </p>
        <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
        <div class="d-flex align-items-center">
           <div class="user-img" style="background-image: url(images/person_2.jpg)"></div>
           <div class="pl-3">
              <p class="name">Roger Scott</p>
              <span class="position">Marketing Manager</span>
          </div>
      </div>
  </div>
</div>
</div>
<div class="item">
    <div class="testimony-wrap py-4">
      <div class="text">
         <p class="star">
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
        </p>
        <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
        <div class="d-flex align-items-center">
           <div class="user-img" style="background-image: url(images/person_3.jpg)"></div>
           <div class="pl-3">
              <p class="name">Roger Scott</p>
              <span class="position">Marketing Manager</span>
          </div>
      </div>
  </div>
</div>
</div>
<div class="item">
    <div class="testimony-wrap py-4">
      <div class="text">
         <p class="star">
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
        </p>
        <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
        <div class="d-flex align-items-center">
           <div class="user-img" style="background-image: url(images/person_1.jpg)"></div>
           <div class="pl-3">
              <p class="name">Roger Scott</p>
              <span class="position">Marketing Manager</span>
          </div>
      </div>
  </div>
</div>
</div>
<div class="item">
    <div class="testimony-wrap py-4">
      <div class="text">
         <p class="star">
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
            <span class="fa fa-star"></span>
        </p>
        <p class="mb-4">Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
        <div class="d-flex align-items-center">
           <div class="user-img" style="background-image: url(images/person_2.jpg)"></div>
           <div class="pl-3">
              <p class="name">Roger Scott</p>
              <span class="position">Marketing Manager</span>
          </div>
      </div>
  </div>
</div>
</div>
</div>
</div>
</div>
</div>
</section>

<section class="ftco-intro ftco-section ftco-no-pb">
 <div class="container">
    <div class="row justify-content-center">
       <div class="col-md-12 text-center">
          <div class="img"  style="background-image: url(images/bg_4.jpg);">
             <div class="overlay"></div>
             <h2>We Are StudyLab An Online Learning Center</h2>
             <p>We can manage your dream building A small river named Duden flows by their place</p>
             <p class="mb-0"><a href="#" class="btn btn-primary px-4 py-3">Enroll Now</a></p>
         </div>
     </div>
 </div>
</div>
</section>

<section class="ftco-section services-section">
  <div class="container">
    <div class="row d-flex">
      <div class="col-md-6 heading-section pr-md-5 ftco-animate d-flex align-items-center">
         <div class="w-100 mb-4 mb-md-0">
            <span class="subheading">Welcome to StudyLab</span>
            <h2 class="mb-4">We Are StudyLab An Online Learning Center</h2>
            <p>A small river named Duden flows by their place and supplies it with the necessary regelialia. It is a paradisematic country, in which roasted parts of sentences fly into your mouth.</p>
            <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts. Separated they live in Bookmarksgrove right at the coast of the Semantics, a large language ocean.</p>
            <div class="d-flex video-image align-items-center mt-md-4">
              <a href="#" class="video img d-flex align-items-center justify-content-center" style="background-image: url(images/about.jpg);">
                 <span class="fa fa-play-circle"></span>
             </a>
             <h4 class="ml-4">Learn anything from StudyLab, Watch video</h4>
         </div>
     </div>
 </div>
 <div class="col-md-6">
     <div class="row">
        <div class="col-md-12 col-lg-6 d-flex align-self-stretch ftco-animate">
          <div class="services">
            <div class="icon d-flex align-items-center justify-content-center"><span class="flaticon-tools"></span></div>
            <div class="media-body">
              <h3 class="heading mb-3">Top Quality Content</h3>
              <p>A small river named Duden flows by their place and supplies</p>
          </div>
      </div>      
  </div>
  <div class="col-md-12 col-lg-6 d-flex align-self-stretch ftco-animate">
      <div class="services">
        <div class="icon icon-2 d-flex align-items-center justify-content-center"><span class="flaticon-instructor"></span></div>
        <div class="media-body">
          <h3 class="heading mb-3">Highly Skilled Instructor</h3>
          <p>A small river named Duden flows by their place and supplies</p>
      </div>
  </div>    
</div>
<div class="col-md-12 col-lg-6 d-flex align-self-stretch ftco-animate">
  <div class="services">
    <div class="icon icon-3 d-flex align-items-center justify-content-center"><span class="flaticon-quiz"></span></div>
    <div class="media-body">
      <h3 class="heading mb-3">World Class &amp; Quiz</h3>
      <p>A small river named Duden flows by their place and supplies</p>
  </div>
</div>      
</div>
<div class="col-md-12 col-lg-6 d-flex align-self-stretch ftco-animate">
  <div class="services">
    <div class="icon icon-4 d-flex align-items-center justify-content-center"><span class="flaticon-browser"></span></div>
    <div class="media-body">
      <h3 class="heading mb-3">Get Certified</h3>
      <p>A small river named Duden flows by their place and supplies</p>
  </div>
</div>      
</div>
</div>
</div>
</div>
</div>
</section>


<section class="ftco-section bg-light">
  <div class="container">
     <div class="row justify-content-center pb-4">
      <div class="col-md-12 heading-section text-center ftco-animate">
         <span class="subheading">Our Blog</span>
         <h2 class="mb-4">Recent Post</h2>
     </div>
 </div>
 <div class="row d-flex">
  <div class="col-lg-4 ftco-animate">
    <div class="blog-entry">
      <a href="blog-single.html" class="block-20" style="background-image: url('images/image_1.jpg');">
      </a>
      <div class="text d-block">
         <div class="meta">
          <p>
             <a href="#"><span class="fa fa-calendar mr-2"></span>Sept. 17, 2020</a>
             <a href="#"><span class="fa fa-user mr-2"></span>Admin</a>
             <a href="#" class="meta-chat"><span class="fa fa-comment mr-2"></span> 3</a>
         </p>
     </div>
     <h3 class="heading"><a href="#">I'm not creative, Should I take this course?</a></h3>
     <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia...</p>
     <p><a href="blog.html" class="btn btn-secondary py-2 px-3">Read more</a></p>
 </div>
</div>
</div>

<div class="col-lg-4 ftco-animate">
    <div class="blog-entry">
      <a href="blog-single.html" class="block-20" style="background-image: url('images/image_2.jpg');">
      </a>
      <div class="text d-block">
         <div class="meta">
          <p>
             <a href="#"><span class="fa fa-calendar mr-2"></span>Sept. 17, 2020</a>
             <a href="#"><span class="fa fa-user mr-2"></span>Admin</a>
             <a href="#" class="meta-chat"><span class="fa fa-comment mr-2"></span> 3</a>
         </p>
     </div>
     <h3 class="heading"><a href="#">I'm not creative, Should I take this course?</a></h3>
     <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia...</p>
     <p><a href="blog.html" class="btn btn-secondary py-2 px-3">Read more</a></p>
 </div>
</div>
</div>
<div class="col-lg-4 ftco-animate">
    <div class="blog-entry">
      <a href="blog-single.html" class="block-20" style="background-image: url('images/image_3.jpg');">
      </a>
      <div class="text d-block">
         <div class="meta">
          <p>
             <a href="#"><span class="fa fa-calendar mr-2"></span>Sept. 17, 2020</a>
             <a href="#"><span class="fa fa-user mr-2"></span>Admin</a>
             <a href="#" class="meta-chat"><span class="fa fa-comment mr-2"></span> 3</a>
         </p>
     </div>
     <h3 class="heading"><a href="#">I'm not creative, Should I take this course?</a></h3>
     <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia...</p>
     <p><a href="blog.html" class="btn btn-secondary py-2 px-3">Read more</a></p>
 </div>
</div>
</div>
</div>
</div>
</section>


<footer class="ftco-footer ftco-no-pt">
  <div class="container">
    <div class="row mb-5">
      <div class="col-md pt-5">
        <div class="ftco-footer-widget pt-md-5 mb-4">
          <h2 class="ftco-heading-2">About</h2>
          <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
          <ul class="ftco-footer-social list-unstyled float-md-left float-lft">
            <li class="ftco-animate"><a href="#"><span class="fa fa-twitter"></span></a></li>
            <li class="ftco-animate"><a href="#"><span class="fa fa-facebook"></span></a></li>
            <li class="ftco-animate"><a href="#"><span class="fa fa-instagram"></span></a></li>
        </ul>
    </div>
</div>
<div class="col-md pt-5">
    <div class="ftco-footer-widget pt-md-5 mb-4 ml-md-5">
      <h2 class="ftco-heading-2">Help Desk</h2>
      <ul class="list-unstyled">
        <li><a href="#" class="py-2 d-block">Customer Care</a></li>
        <li><a href="#" class="py-2 d-block">Legal Help</a></li>
        <li><a href="#" class="py-2 d-block">Services</a></li>
        <li><a href="#" class="py-2 d-block">Privacy and Policy</a></li>
        <li><a href="#" class="py-2 d-block">Refund Policy</a></li>
        <li><a href="#" class="py-2 d-block">Call Us</a></li>
    </ul>
</div>
</div>
<div class="col-md pt-5">
   <div class="ftco-footer-widget pt-md-5 mb-4">
      <h2 class="ftco-heading-2">Recent Courses</h2>
      <ul class="list-unstyled">
        <li><a href="#" class="py-2 d-block">Computer Engineering</a></li>
        <li><a href="#" class="py-2 d-block">Web Design</a></li>
        <li><a href="#" class="py-2 d-block">Business Studies</a></li>
        <li><a href="#" class="py-2 d-block">Civil Engineering</a></li>
        <li><a href="#" class="py-2 d-block">Computer Technician</a></li>
        <li><a href="#" class="py-2 d-block">Web Developer</a></li>
    </ul>
</div>
</div>
<div class="col-md pt-5">
    <div class="ftco-footer-widget pt-md-5 mb-4">
       <h2 class="ftco-heading-2">Have a Questions?</h2>
       <div class="block-23 mb-3">
         <ul>
           <li><span class="icon fa fa-map-marker"></span><span class="text">203 Fake St. Mountain View, San Francisco, California, USA</span></li>
           <li><a href="#"><span class="icon fa fa-phone"></span><span class="text">+2 392 3929 210</span></a></li>
           <li><a href="#"><span class="icon fa fa-paper-plane"></span><span class="text">info@yourdomain.com</span></a></li>
       </ul>
   </div>
</div>
</div>
</div>
<div class="row">
  <div class="col-md-12 text-center">

    <p>< Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. >
      Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="fa fa-heart" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">Colorlib</a>
      <! Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. ></p>
  </div>
</div>
</div>
</footer-->



    <!-- loader -->
    <!--div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div -->


    <!--script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '{your-app-id}',
      cookie     : true,
      xfbml      : true,
      version    : 'v9.0'
    });
      
    FB.AppEvents.logPageView();   
      
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "https://connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script-->

    <script src="{{ asset('js/supplementary/jquery-migrate-3.0.1.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/popper.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/jquery.easing.1.3.js') }}"></script>
    <script src="{{ asset('js/supplementary/jquery.waypoints.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/jquery.stellar.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/jquery.animateNumber.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('js/supplementary/scrollax.min.js') }}"></script>
    <script src="{{ asset('js/supplementary/main.js') }}"></script>



</body>

</html>