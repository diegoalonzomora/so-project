const $ = (s)=>document.querySelector(s);
$('#year').textContent = new Date().getFullYear();

const API_BASE = new URL('../api/v1/', window.location.href).href;
const API = {
  hoteles: new URL('hotel.php', API_BASE).href,
  habitaciones: new URL('habitacion.php', API_BASE).href,
  paises: new URL('pais.php', API_BASE).href,
  cliente: new URL('cliente.php', API_BASE).href,
  reserva: new URL('reserva.php', API_BASE).href,
  servicios: new URL('servicios_adicionales.php', API_BASE).href,
  factura: new URL('factura.php', API_BASE).href,
};

const params = new URLSearchParams(location.search);
const habitacionId = Number(params.get('habitacionId') || 0);
const hotelId = Number(params.get('hotelId') || 0);

$('#habitacionId').value = habitacionId;
$('#hotelId').value = hotelId;

let hotelData = null;
let habitacionData = null;
let serviciosDisponibles = [];

let entradaPicker = null;
let salidaPicker = null;

document.addEventListener('DOMContentLoaded', function() {
  if (typeof flatpickr !== 'undefined') {
    entradaPicker = flatpickr('#entrada', {
      locale: 'es',
      dateFormat: 'Y-m-d',
      minDate: 'today',
      onChange: function(selectedDates, dateStr) {
        if (salidaPicker && dateStr) {
          const minSalida = new Date(dateStr);
          minSalida.setDate(minSalida.getDate() + 1);
          salidaPicker.set('minDate', minSalida);
        }
        actualizarResumen();
      },
      theme: 'dark'
    });

    salidaPicker = flatpickr('#salida', {
      locale: 'es',
      dateFormat: 'Y-m-d',
      minDate: 'today',
      onChange: function() {
        actualizarResumen();
      },
      theme: 'dark'
    });
  }
});

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

function calcularTotal() {
  if (!habitacionData) return 0;
  
  const entrada = $('#entrada').value;
  const salida = $('#salida').value;
  
  if (!entrada || !salida) return 0;
  
  const dias = (new Date(salida) - new Date(entrada)) / (1000 * 60 * 60 * 24);
  if (dias <= 0) return 0;
  
  const precioHab = Number(habitacionData.precioNoche || 0) * dias;
  
  const checkboxes = document.querySelectorAll('input[name="servicios"]:checked');
  let precioServicios = 0;
  checkboxes.forEach(cb => {
    precioServicios += Number(cb.dataset.precio || 0);
  });
  
  return precioHab + precioServicios;
}

function actualizarResumen() {
  const entrada = $('#entrada').value;
  const salida = $('#salida').value;
  
  if (!habitacionData) {
    $('#totalAmount').textContent = '$0.00';
    return;
  }
  
  if (!entrada || !salida) {
    $('#totalAmount').textContent = '—';
    $('#totalAmount').parentElement.querySelector('span:first-child').textContent = 'Selecciona fechas';
    return;
  }
  
  const dias = (new Date(salida) - new Date(entrada)) / (1000 * 60 * 60 * 24);
  
  if (dias <= 0) {
    $('#totalAmount').textContent = '—';
    $('#totalAmount').parentElement.querySelector('span:first-child').textContent = 'Fechas inválidas';
    return;
  }
  
  $('#totalAmount').parentElement.querySelector('span:first-child').textContent = 'Total';
  
  const precioHab = Number(habitacionData.precioNoche || 0) * dias;
  
  const checkboxes = document.querySelectorAll('input[name="servicios"]:checked');
  let precioServicios = 0;
  checkboxes.forEach(cb => {
    precioServicios += Number(cb.dataset.precio || 0);
  });
  
  const total = precioHab + precioServicios;
  $('#totalAmount').textContent = `S/ ${total.toFixed(2)}`;
  
  const desglose = `
    <div style="font-size: 0.9rem; color: var(--muted); margin-top: 8px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.1)">
      <div style="display: flex; justify-content: space-between; margin-bottom: 4px">
        <span>${dias} ${dias === 1 ? 'noche' : 'noches'} × S/ ${Number(habitacionData.precioNoche || 0).toFixed(2)}</span>
        <span>S/ ${precioHab.toFixed(2)}</span>
      </div>
      ${precioServicios > 0 ? `
        <div style="display: flex; justify-content: space-between">
          <span>Servicios adicionales</span>
          <span>S/ ${precioServicios.toFixed(2)}</span>
        </div>
      ` : ''}
    </div>
  `;
  
  let desgloseContainer = document.getElementById('desgloseContainer');
  if (!desgloseContainer) {
    desgloseContainer = document.createElement('div');
    desgloseContainer.id = 'desgloseContainer';
    $('#totalAmount').parentElement.appendChild(desgloseContainer);
  }
  desgloseContainer.innerHTML = desglose;
}

(async function loadDetail(){
  try {
    const [hoteles, habitaciones] = await Promise.all([
      fetchJSON(API.hoteles),
      fetchJSON(API.habitaciones),
    ]);

    hotelData = (Array.isArray(hoteles)?hoteles:[]).find(h=>Number(h.idHotel)===hotelId) || {};
    habitacionData = (Array.isArray(habitaciones)?habitaciones:[]).find(h=>Number(h.idHabitacion)===habitacionId) || {};

    $('#hotelName').textContent = hotelData.nombreHotel || 'Hotel';
    $('#roomTitle').textContent = habitacionData.tipoHabitacion || 'Habitación';
    $('#roomMeta').textContent = `${hotelData.ciudad || ''}${hotelData.ciudad && hotelData.nombrePais ? ' · ' : ''}${hotelData.nombrePais || ''}`;
    $('#roomPrice').textContent = habitacionData.precioNoche ? `S/ ${Number(habitacionData.precioNoche).toFixed(2)} / noche` : '';
    $('#roomExtra').textContent = `Código: ${habitacionData.codigoHabitacion || '—'} · Capacidad: ${habitacionData.capacidad ?? '—'}`;
    $('#roomImg').src = pickImage(habitacionData);
    
    if (hotelId) {
      const serviciosUrl = `${API.servicios}?idHotel=${hotelId}`;
      const serviciosData = await fetchJSON(serviciosUrl);
      serviciosDisponibles = Array.isArray(serviciosData) ? serviciosData : (serviciosData?.data || []);
      
      const serviciosContainer = $('#serviciosAdicionales');
      serviciosContainer.innerHTML = '';
      
      if (serviciosDisponibles.length > 0) {
        serviciosDisponibles.forEach(servicio => {
          const div = document.createElement('div');
          div.className = 'servicio-item';
          div.innerHTML = `
            <label>
              <input type="checkbox" name="servicios" value="${servicio.idServicioAdicional}" 
                     data-precio="${servicio.precioAdicional}">
              <span><strong>${servicio.nombre}</strong> - S/ ${Number(servicio.precioAdicional || 0).toFixed(2)}</span>
              ${servicio.descripcion ? `<br><small class="muted">${servicio.descripcion}</small>` : ''}
            </label>
          `;
          serviciosContainer.appendChild(div);
        });
        
        document.querySelectorAll('input[name="servicios"]').forEach(cb => {
          cb.addEventListener('change', actualizarResumen);
        });
      } else {
        serviciosContainer.innerHTML = '<p class="muted">No hay servicios adicionales disponibles para este hotel.</p>';
      }
    }
    
    actualizarResumen();
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

let clienteExistente = null;

$('#buscarClienteBtn').addEventListener('click', async () => {
  const dniBuscar = $('#dniBuscar').value.trim();
  
  if (!dniBuscar) {
    showMsg('Por favor ingresa un DNI', false);
    return;
  }
  
  try {
    $('#buscarClienteBtn').disabled = true;
    $('#buscarClienteBtn').textContent = 'Buscando...';
    
    const response = await fetch(`${API.cliente}?documentoIdentidad=${encodeURIComponent(dniBuscar)}`);
    const data = await response.json();
    
    if (response.ok && data && data.idCliente) {
      clienteExistente = data;
      
      $('#clienteEncontrado').style.display = 'block';
      $('#nombreClienteEncontrado').textContent = `${data.nombres} ${data.apellidoPaterno} ${data.apellidoMaterno}`;
      
      $('#camposCliente').style.display = 'none';
      
      $('#idClienteExistente').value = data.idCliente;
      
      showMsg('Cliente encontrado exitosamente. Puedes proceder con la reserva.', true);
    } else {
      clienteExistente = null;
      $('#clienteEncontrado').style.display = 'none';
      $('#camposCliente').style.display = 'grid';
      $('#idClienteExistente').value = '';
      
      $('#documentoIdentidad').value = dniBuscar;
      
      showMsg('Cliente no encontrado. Por favor completa tus datos para registrarte.', true);
    }
  } catch (error) {
    console.error('Error al buscar cliente:', error);
    
    clienteExistente = null;
    $('#clienteEncontrado').style.display = 'none';
    $('#camposCliente').style.display = 'grid';
    $('#idClienteExistente').value = '';
    $('#documentoIdentidad').value = dniBuscar;
    
    showMsg('No se pudo verificar el DNI. Por favor completa tus datos para continuar.', true);
  } finally {
    $('#buscarClienteBtn').disabled = false;
    $('#buscarClienteBtn').textContent = 'Buscar';
  }
});

$('#clienteForm').addEventListener('submit', async (e)=>{
  e.preventDefault();
  $('#msg').hidden = true;

  const fd = Object.fromEntries(new FormData(e.currentTarget).entries());

  if (!fd.entrada || !fd.salida) {
    showMsg('Por favor selecciona las fechas de entrada y salida', false);
    return;
  }

  if (!fd.idClienteExistente || !clienteExistente) {
    const camposRequeridos = [
      { campo: 'documentoIdentidad', nombre: 'DNI' },
      { campo: 'nombres', nombre: 'Nombres' },
      { campo: 'apellidoPaterno', nombre: 'Apellido paterno' },
      { campo: 'apellidoMaterno', nombre: 'Apellido materno' },
      { campo: 'correo', nombre: 'Correo' },
      { campo: 'idPais', nombre: 'País' },
      { campo: 'ciudad', nombre: 'Ciudad' }
    ];

    for (const item of camposRequeridos) {
      if (!fd[item.campo] || fd[item.campo].trim() === '') {
        showMsg(`Por favor completa el campo: ${item.nombre}`, false);
        return;
      }
    }
  }

  if (!fd.metodoPago) {
    showMsg('Por favor selecciona un método de pago', false);
    return;
  }

  const serviciosSeleccionados = [];
  document.querySelectorAll('input[name="servicios"]:checked').forEach(cb => {
    serviciosSeleccionados.push(Number(cb.value));
  });

  const entrada = fd.entrada;
  const salida = fd.salida;
  const dias = (new Date(salida) - new Date(entrada)) / (1000 * 60 * 60 * 24);
  const precioHab = Number(habitacionData.precioNoche || 0) * dias;
  let precioServicios = 0;
  document.querySelectorAll('input[name="servicios"]:checked').forEach(cb => {
    precioServicios += Number(cb.dataset.precio || 0);
  });
  const total = precioHab + precioServicios;

  if (total <= 0) {
    showMsg('El monto total debe ser mayor a cero', false);
    return;
  }

  try{
    let idCliente;
    
    if (fd.idClienteExistente && clienteExistente) {
      idCliente = Number(fd.idClienteExistente);
    } else {
      const clientePayload = {
        numeroTelefono: fd.numeroTelefono || '',
        nombres: fd.nombres,
        apellidoPaterno: fd.apellidoPaterno,
        apellidoMaterno: fd.apellidoMaterno,
        correo: fd.correo,
        idPais: Number(fd.idPais),
        ciudad: fd.ciudad,
        documentoIdentidad: fd.documentoIdentidad,
        fechaRegistro: todayISO(),
      };

      const resCliente = await fetch(API.cliente, {
        method:'POST',
        headers:{ 'Accept':'application/json','Content-Type':'application/json' },
        body: JSON.stringify(clientePayload)
      });
      const bodyCliente = await resCliente.json().catch(()=>null);
      if(!resCliente.ok || bodyCliente?.error){
        throw new Error(bodyCliente?.error || `Error creando cliente`);
      }
      idCliente = bodyCliente?.data?.idCliente;
      if(!idCliente) throw new Error('No se recibió idCliente');
    }

    const facturaPayload = {
      montoTotal: total,
      fechaPago: todayISO(),
      metodoPago: fd.metodoPago || 'Tarjeta',
      descuento: 0
    };

    const resFactura = await fetch(API.factura, {
      method:'POST',
      headers:{ 'Accept':'application/json','Content-Type':'application/json' },
      body: JSON.stringify(facturaPayload)
    });
    const bodyFactura = await resFactura.json().catch(()=>null);
    if(!resFactura.ok || bodyFactura?.error){
      throw new Error(bodyFactura?.error || `Error creando factura`);
    }
    const idFactura = bodyFactura?.data?.idFactura;
    if(!idFactura) throw new Error('No se recibió idFactura');

    const reservaPayload = {
      idHabitacion: habitacionId,
      idCliente: idCliente,
      fechaEntrada: fd.entrada,
      fechaSalida: fd.salida,
      estadoReserva: 'Confirmada',
      idFactura: idFactura,
      serviciosAdicionales: serviciosSeleccionados
    };

    const resReserva = await fetch(API.reserva, {
      method:'POST',
      headers:{ 'Accept':'application/json','Content-Type':'application/json' },
      body: JSON.stringify(reservaPayload)
    });
    const bodyReserva = await resReserva.json().catch(()=>null);
    if(!resReserva.ok || bodyReserva?.error){
      throw new Error(bodyReserva?.error || `Error creando reserva`);
    }
    const idReserva = bodyReserva?.data?.idReserva || bodyReserva?.idReserva;

    const qs = new URLSearchParams({
      idReserva, idFactura, idCliente
    }).toString();
    location.href = `../confirmacion/exito.html?${qs}`;

  }catch(err){
    console.error(err);
    showMsg(err.message || 'No se pudo completar la reserva', false);
  }
});

$('#cancelBtn').addEventListener('click', ()=>{
  history.length>1 ? history.back() : location.href='../index.html';
});
