// /confirmacion/app.js
const $ = (s)=>document.querySelector(s);
$('#year').textContent = new Date().getFullYear();

const p = new URLSearchParams(location.search);
const hotelId = p.get('hotelId');
const habitacionId = p.get('habitacionId');
const entrada = p.get('entrada');
const salida = p.get('salida');
const idCliente = p.get('idCliente');
const idReserva = p.get('idReserva');

const info = [];
if (idReserva) info.push(`Reserva N.º ${idReserva}`);
if (entrada && salida) info.push(`Del ${entrada} al ${salida}`);
if (habitacionId) info.push(`Habitación ID ${habitacionId}`);
if (hotelId) info.push(`Hotel ID ${hotelId}`);
if (idCliente) info.push(`Cliente ID ${idCliente}`);

$('#summary').textContent = info.length
  ? info.join(' · ')
  : 'Tu reserva fue registrada correctamente.';
