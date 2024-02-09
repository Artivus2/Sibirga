/**
 * Text = 3D Text
 *
 * parameters = {
 *  font: <THREE.Font>, // font
 *
 *  size: <float>, // size of the text
 *  height: <float>, // thickness to extrude text
 *  curveSegments: <int>, // number of points on the curves
 *
 *  bevelEnabled: <bool>, // turn on bevel
 *  bevelThickness: <float>, // how deep into text bevel goes
 *  bevelSize: <float>, // how far from text outline (including bevelOffset) is bevel
 *  bevelOffset: <float> // how far from text outline does bevel start
 * }
 */

/*
 import {
	ExtrudeGeometry
} from 'three';
*/

const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 1, 1000);
const renderer = new THREE.WebGLRenderer({antialias:true, alpha:true});
renderer.setClearColor(0x000000,0);
console.log(scene);
document.getElementById("appn").appendChild(renderer.domElement);
//$('#appn').append(renderer.domElement);
//var light = new THREE.DirectionalLight(0xd9d9d9, 0.5);
//light.position.setScalar(10);
//scene.add(light);
//scene.add(new THREE.AmbientLight(0xd9d9d9, 0.5));
const geometry = new THREE.BoxGeometry(1,1,1);
console.log(geometry);

const material = new THREE.MeshBasicMaterial({color: 0x61AF6A, wireframe: true});
const cube = new THREE.Mesh(geometry, material);
//scene.fog = new THREE.Fog(0xcccccc, 10, 15);
scene.add(cube);

camera.position.z = 3;
function animate() {
renderer.setSize(window.innerWidth/3, window.innerHeight/3);
requestAnimationFrame(animate);
cube.rotation.x += 0.01;
cube.rotation.y -= 0.01;
//camera.position.z -= 0.01;
renderer.render(scene, camera);
}
animate();

/*
class TextGeometry extends ExtrudeGeometry {

	constructor( text, parameters = {} ) {

		const font = parameters.font;

		if ( font === undefined ) {

			super(); // generate default extrude geometry

		} else {

			const shapes = font.generateShapes( text, parameters.size );

			// translate parameters to ExtrudeGeometry API

			parameters.depth = parameters.height !== undefined ? parameters.height : 50;

			// defaults

			if ( parameters.bevelThickness === undefined ) parameters.bevelThickness = 10;
			if ( parameters.bevelSize === undefined ) parameters.bevelSize = 8;
			if ( parameters.bevelEnabled === undefined ) parameters.bevelEnabled = false;

			super( shapes, parameters );

		}

		this.type = 'TextGeometry';

	}

}


export { TextGeometry };
*/