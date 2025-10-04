// /reserva/app.js

const $ = (s)=>document.querySelector(s);
$('#year').textContent = new Date().getFullYear();

const API_BASE = new URL('../api/v1/', window.location.href).href;
const API = {
  hoteles: new URL('hotel.php', API_BASE).href,
  habitaciones: new URL('habitacion.php', API_BASE).href,
  paises: new URL('pais.php', API_BASE).href,
  cliente: new URL('cliente.php', API_BASE).href,
  reserva: new URL('reserva.php', API_BASE).href,
};

const params = new URLSearchParams(location.search);
const habitacionId = Number(params.get('habitacionId') || 0);
const hotelId = Number(params.get('hotelId') || 0);
const entrada = params.get('entrada') || '';
const salida = params.get('salida') || '';

$('#entrada').value = entrada;
$('#salida').value = salida;
$('#habitacionId').value = habitacionId;
$('#hotelId').value = hotelId;

async function fetchJSON(url){
  const r = await fetch(url, { headers:{Accept:'application/json'}});
  const ct = r.headers.get('content-type')||'';
  if (!r.ok) throw new Error(`HTTP ${r.status} ${url}`);
  if (!ct.includes('application/json')) throw new Error(`No JSON en ${url}`);
  return r.json();
}

function pickImage(room){
  const tipo = (room?.tipoHabitacion || '').toLowerCase();
  if (tipo.includes('suite')) return 'https://images.unsplash.com/photo-1560066984-138dadb4c035?q=80&w=1400&auto=format&fit=crop';
  if (tipo.includes('deluxe') || tipo.includes('ejecutivo')) return 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=1400&auto=format&fit=crop';
  return 'https://images.unsplash.com/photo-1501183638710-841dd1904471?q=80&w=1400&auto=format&fit=crop';
}

function showMsg(text, ok=true){
  const box = $('#msg');
  box.hidden = false;
  box.className = `msg ${ok ? 'ok' : 'err'}`;
  box.textContent = text;
}

(async function loadDetail(){
  try {
    const [hoteles, habitaciones] = await Promise.all([
      fetchJSON(API.hoteles),
      fetchJSON(API.habitaciones),
    ]);

    const hotel = (Array.isArray(hoteles)?hoteles:[]).find(h=>Number(h.idHotel)===hotelId) || {};
    const hab = (Array.isArray(habitaciones)?habitaciones:[]).find(h=>Number(h.idHabitacion)===habitacionId) || {};

    $('#hotelName').textContent = hotel.nombreHotel || 'Hotel';
    $('#roomTitle').textContent = hab.tipoHabitacion || 'Habitación';
    $('#roomMeta').textContent = `${hotel.ciudad || ''}${hotel.ciudad && hotel.nombrePais ? ' · ' : ''}${hotel.nombrePais || ''}`;
    $('#roomPrice').textContent = hab.precioNoche ? `$${Number(hab.precioNoche).toFixed(2)} / noche` : '';
    $('#roomExtra').textContent = `Código: ${hab.codigoHabitacion || '—'} · Capacidad: ${hab.capacidad ?? '—'}`;
    $('#roomImg').src = pickImage(hab);
  } catch(e){
    console.error(e);
    showMsg('No pudimos cargar el detalle.', false);
  }
})();

(async function loadCountries(){
  const sel = $('#paisSelect');
  try{
    const data = await fetchJSON(API.paises);
    const paises = Array.isArray(data) ? data : (data?.data || []);
    paises
      .filter(p=>p && p.idPais)
      .sort((a,b)=>(a.nombrePais||'').localeCompare(b.nombrePais||''))
      .forEach(p=>{
        const opt=document.createElement('option');
        opt.value=p.idPais;
        opt.textContent=p.nombrePais||`País ${p.idPais}`;
        sel.appendChild(opt);
      });
  }catch(e){
    console.error(e);
    ['Perú','Chile','México'].forEach((n,i)=>{
      const opt=document.createElement('option');
      opt.value=i+1; opt.textContent=n; sel.appendChild(opt);
    });
  }
})();

function todayISO(){
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}

$('#clienteForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  $('#msg').hidden = true;

  const fd = Object.fromEntries(new FormData(e.currentTarget).entries());

  // 1) Crear Cliente
  const clientePayload = new URLSearchParams({
    numeroTelefono: fd.numeroTelefono || '',
    nombres: fd.nombres,
    apellidoPaterno: fd.apellidoPaterno,
    apellidoMaterno: fd.apellidoMaterno,
    correo: fd.correo,
    idPais: fd.idPais,
    ciudad: fd.ciudad,
    documentoIdentidad: fd.documentoIdentidad,
    fechaRegistro: todayISO(),
  }).toString();

  try{
    const resCliente = await fetch(API.cliente, {
      method:'POST',
      headers:{ 'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
      body: clientePayload
    });
    const bodyCliente = await resCliente.json().catch(()=>null);
    if(!resCliente.ok || bodyCliente?.error){
      throw new Error(bodyCliente?.error || `Error creando cliente`);
    }
    const idCliente = bodyCliente?.data?.idCliente;
    if(!idCliente) throw new Error('No se recibió idCliente');

    // 2) Crear Reserva
    const reservaPayload = new URLSearchParams({
      idHabitacion: String(habitacionId),
      idCliente: String(idCliente),
      fechaEntrada: entrada,
      fechaSalida: salida,
      estadoReserva: 'pendiente'
    }).toString();

    const resReserva = await fetch(API.reserva, {
      method:'POST',
      headers:{ 'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded; charset=UTF-8' },
      body: reservaPayload
    });
    const bodyReserva = await resReserva.json().catch(()=>null);
    if(!resReserva.ok || bodyReserva?.error){
      throw new Error(bodyReserva?.error || `Error creando reserva`);
    }
    const idReserva = bodyReserva?.data?.idReserva;

    // 3) Redirigir a confirmación
    const qs = new URLSearchParams({
      hotelId, habitacionId, entrada, salida, idCliente, idReserva
    }).toString();
    location.href = `../confirmacion/exito.html?${qs}`;

  }catch(err){
    console.error(err);
    showMsg(err.message || 'No se pudo completar la reserva', false);
  }
});

// Cancelar
$('#cancelBtn').addEventListener('click', ()=>{
  history.length>1 ? history.back() : location.href='../index.html';
});
