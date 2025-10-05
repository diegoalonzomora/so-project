// /confirmacion/app.js
const $ = (s)=>document.querySelector(s);
const $$ = (s)=>document.querySelectorAll(s);
$('#year').textContent = new Date().getFullYear();

const API_BASE = new URL('../api/v1/', window.location.href).href;
const API = {
  reserva: new URL('reserva.php', API_BASE).href,
  factura: new URL('factura.php', API_BASE).href,
  servicios: new URL('servicios_adicionales.php', API_BASE).href,
};

const p = new URLSearchParams(location.search);
const idReserva = p.get('idReserva');
const idFactura = p.get('idFactura');
const idCliente = p.get('idCliente');

let reservaData = null;
let serviciosDisponibles = [];

async function fetchJSON(url){
  const r = await fetch(url, { headers:{Accept:'application/json'}});
  if (!r.ok) throw new Error(`HTTP ${r.status}`);
  const ct = r.headers.get('content-type')||'';
  if (!ct.includes('application/json')) throw new Error('No JSON');
  return r.json();
}

function calcularNoches(entrada, salida) {
  const dias = (new Date(salida) - new Date(entrada)) / (1000 * 60 * 60 * 24);
  return Math.max(1, dias);
}

async function cargarFactura() {
  try {
    if (!idReserva) {
      throw new Error('No se proporcionó ID de reserva');
    }

    // Cargar datos de la reserva (usar parámetro 'idReserva' que acepta la API)
    const reservaUrl = `${API.reserva}?idReserva=${idReserva}`;
    reservaData = await fetchJSON(reservaUrl);

    if (!reservaData || reservaData.error) {
      throw new Error(reservaData?.error || 'No se encontró la reserva');
    }

    console.log('Datos de reserva:', reservaData); // Para debug

    // Mostrar información básica
    $('#idReserva').textContent = reservaData.idReserva || '—';
    $('#idFactura').textContent = reservaData.idFactura || '—';
    $('#fechaEmision').textContent = reservaData.fechaPago || new Date().toLocaleDateString('es-ES');
    $('#nombreHotel').textContent = reservaData.nombreHotel || '—';
    $('#tipoHabitacion').textContent = reservaData.tipoHabitacion ? 
      `${reservaData.tipoHabitacion} (${reservaData.codigoHabitacion || ''})` : '—';
    $('#ubicacionHotel').textContent = `${reservaData.ciudad || '—'}, ${reservaData.nombrePais || '—'}`;
    
    const nombreCompleto = [
      reservaData.nombres,
      reservaData.apellidoPaterno,
      reservaData.apellidoMaterno
    ].filter(Boolean).join(' ');
    $('#nombreCliente').textContent = nombreCompleto || '—';
    $('#documentoCliente').textContent = reservaData.documentoIdentidad || '—';
    $('#correoCliente').textContent = reservaData.correo || '—';

    $('#fechaEntrada').textContent = reservaData.fechaEntrada || '—';
    $('#fechaSalida').textContent = reservaData.fechaSalida || '—';
    
    const noches = calcularNoches(reservaData.fechaEntrada, reservaData.fechaSalida);
    $('#numNoches').textContent = noches;
    $('#nochesCount').textContent = noches;

    // Calcular costo de habitación
    const precioNoche = Number(reservaData.precioNoche || 0);
    const totalHabitacion = precioNoche * noches;
    $('#precioHabitacion').textContent = `S/ ${totalHabitacion.toFixed(2)}`;

    // Mostrar servicios adicionales
    const serviciosContainer = $('#serviciosContainer');
    serviciosContainer.innerHTML = '';
    
    if (reservaData.serviciosAdicionales && reservaData.serviciosAdicionales.length > 0) {
      const divServicios = document.createElement('div');
      divServicios.className = 'servicios-list';
      divServicios.innerHTML = '<div style="font-weight: 600; margin-bottom: 10px; color: #374151">Servicios Adicionales:</div>';
      
      reservaData.serviciosAdicionales.forEach(servicio => {
        const div = document.createElement('div');
        div.className = 'servicio-item-detalle';
        div.innerHTML = `
          <span style="color: #6b7280">${servicio.nombre}</span>
          <strong style="color: #111827">S/ ${Number(servicio.precioAdicional || 0).toFixed(2)}</strong>
        `;
        divServicios.appendChild(div);
      });
      
      const wrapperRow = document.createElement('div');
      wrapperRow.className = 'factura-row';
      wrapperRow.style.display = 'block';
      wrapperRow.appendChild(divServicios);
      serviciosContainer.appendChild(wrapperRow);
    }

    // Mostrar descuento si existe
    const descuento = Number(reservaData.descuento || 0);
    if (descuento > 0) {
      $('#descuentoRow').style.display = 'flex';
      $('#descuentoMonto').textContent = `-S/ ${descuento.toFixed(2)}`;
    }

    // Total
    $('#montoTotal').textContent = `S/ ${Number(reservaData.montoTotal || 0).toFixed(2)}`;
    $('#metodoPago').textContent = reservaData.metodoPago || '—';
    $('#fechaPago').textContent = reservaData.fechaPago || '—';

    // Estado de la reserva
    const estadoBadge = $('#estadoReserva');
    const estado = reservaData.estadoReserva || 'Pendiente';
    estadoBadge.textContent = estado;
    estadoBadge.className = 'status-badge';
    
    if (estado.toLowerCase() === 'cancelada') {
      estadoBadge.classList.add('status-cancelada');
    } else if (estado.toLowerCase() === 'confirmada') {
      estadoBadge.classList.add('status-confirmada');
    } else {
      estadoBadge.classList.add('status-pendiente');
    }

    // Mostrar contenido
    $('#loadingFactura').style.display = 'none';
    $('#facturaContent').style.display = 'block';

  } catch (error) {
    console.error('Error cargando factura:', error);
    $('#loadingFactura').style.display = 'none';
    $('#errorFactura').style.display = 'block';
  }
}

// Modificar servicios
$('#modificarBtn').addEventListener('click', async () => {
  if (!reservaData) return;

  try {
    // Cargar servicios disponibles del hotel
    const hotelId = reservaData.idHotel || 0;
    if (!hotelId) {
      alert('No se pudo identificar el hotel');
      return;
    }

    const serviciosUrl = `${API.servicios}?idHotel=${hotelId}`;
    serviciosDisponibles = await fetchJSON(serviciosUrl);
    if (!Array.isArray(serviciosDisponibles)) serviciosDisponibles = [];

    // Obtener IDs de servicios actuales
    const serviciosActuales = (reservaData.serviciosAdicionales || []).map(s => Number(s.idServicioAdicional));

    // Renderizar modal
    const container = $('#serviciosModificar');
    container.innerHTML = '';

    if (serviciosDisponibles.length === 0) {
      container.innerHTML = '<p class="muted">No hay servicios adicionales disponibles</p>';
    } else {
      serviciosDisponibles.forEach(servicio => {
        const checked = serviciosActuales.includes(Number(servicio.idServicioAdicional));
        const div = document.createElement('div');
        div.style.marginBottom = '12px';
        div.innerHTML = `
          <label style="display: flex; align-items: start; gap: 10px">
            <input type="checkbox" name="servicioModificar" value="${servicio.idServicioAdicional}" ${checked ? 'checked' : ''}>
            <div>
              <strong>${servicio.nombre}</strong> - S/ ${Number(servicio.precioAdicional || 0).toFixed(2)}
              ${servicio.descripcion ? `<br><small class="muted">${servicio.descripcion}</small>` : ''}
            </div>
          </label>
        `;
        container.appendChild(div);
      });
    }

    // Mostrar modal
    $('#modalServicios').style.display = 'flex';

  } catch (error) {
    console.error('Error cargando servicios:', error);
    alert('Error al cargar servicios disponibles');
  }
});

// Cerrar modal
$('#cancelarModificarBtn').addEventListener('click', () => {
  $('#modalServicios').style.display = 'none';
});

// Guardar cambios de servicios
$('#guardarServiciosBtn').addEventListener('click', async () => {
  try {
    // Obtener servicios seleccionados
    const checkboxes = $$('#serviciosModificar input[name="servicioModificar"]:checked');
    const nuevosServicios = Array.from(checkboxes).map(cb => Number(cb.value));

    // Actualizar reserva
    const response = await fetch(`${API.reserva}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        idReserva: Number(idReserva),
        serviciosAdicionales: nuevosServicios
      })
    });

    const result = await response.json();

    if (!response.ok || result.error) {
      throw new Error(result.error || 'Error al actualizar servicios');
    }

    // Cerrar modal y recargar
    $('#modalServicios').style.display = 'none';
    alert('Servicios actualizados correctamente');
    location.reload();

  } catch (error) {
    console.error('Error guardando servicios:', error);
    alert('Error al guardar cambios: ' + error.message);
  }
});

// Cancelar reserva
$('#cancelarBtn').addEventListener('click', async () => {
  if (!confirm('¿Estás seguro de que deseas cancelar esta reserva? Esta acción no se puede deshacer.')) {
    return;
  }

  try {
    const response = await fetch(`${API.reserva}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        idReserva: Number(idReserva),
        cancelar: true
      })
    });

    const result = await response.json();

    if (!response.ok || result.error) {
      throw new Error(result.error || 'Error al cancelar reserva');
    }

    alert('Reserva cancelada exitosamente');
    location.reload();

  } catch (error) {
    console.error('Error cancelando reserva:', error);
    alert('Error al cancelar reserva: ' + error.message);
  }
});

// Cargar factura al iniciar
cargarFactura();
