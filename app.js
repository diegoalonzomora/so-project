// ============== Utilidades ==============
const $ = (sel) => document.querySelector(sel);
const $$ = (sel) => Array.from(document.querySelectorAll(sel));

// BASE URL robusta
const API_BASE = new URL('./api/v1/', window.location.href).href;

const API = {
  hoteles: new URL('hotel.php', API_BASE).href,
  habitaciones: new URL('habitacion.php', API_BASE).href,
  reservas: new URL('reserva.php', API_BASE).href,
  paises: new URL('pais.php', API_BASE).href,
};

// helpers
const fmtMoney = (n) =>
  typeof n === 'number' || (typeof n === 'string' && n.trim() !== '')
    ? `$${Number(n).toFixed(2)}`
    : '$—';

const parseDate = (s) => new Date(`${s}T00:00:00`);
const overlaps = (startA, endA, startB, endB) => !(endA <= startB || startA >= endB);

// Año dinámico
$('#year').textContent = new Date().getFullYear();

// Menú móvil
const hamb = $('#hamb');
const mobile = $('#mobile');
hamb.addEventListener('click', ()=>{
  const show = mobile.style.display === 'block';
  mobile.style.display = show ? 'none' : 'block';
});
window.addEventListener('resize', ()=>{
  if (window.innerWidth > 720) mobile.style.display = 'none';
});

// Scroll suave
$$('a[href^="#"]').forEach(a=>{
  a.addEventListener('click', e=>{
    const id = a.getAttribute('href');
    const target = document.querySelector(id);
    if(target){
      e.preventDefault();
      target.scrollIntoView({behavior:'smooth'});
      mobile.style.display='none';
    }
  });
});

// Fetch JSON con chequeo de content-type
async function fetchJSON(url) {
  const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
  const ctype = res.headers.get('content-type') || '';
  if (!res.ok) {
    const text = await res.text().catch(()=> '');
    throw new Error(`HTTP ${res.status} en ${url}\n${text.slice(0,300)}`);
  }
  if (!ctype.includes('application/json')) {
    const text = await res.text();
    throw new Error(`La API no devolvió JSON en ${url}.\n${text.slice(0,300)}`);
  }
  return res.json();
}

// ====== Poblar select de países ======
async function loadCountries() {
  const sel = $('#paisSelect');
  try {
    const data = await fetchJSON(API.paises);
    const paises = Array.isArray(data) ? data : (data?.data || []);
    // ordenar por nombre
    paises.sort((a,b) => (a.nombrePais||'').localeCompare(b.nombrePais||''));
    // opciones
    for (const p of paises) {
      const opt = document.createElement('option');
      opt.value = p.idPais;                 // usamos idPais para filtrar
      opt.textContent = p.nombrePais || `País ${p.idPais}`;
      sel.appendChild(opt);
    }
  } catch (err) {
    console.error('Error cargando países:', err);
    // fallback mínimo
    const fallback = [
      { idPais: 1, nombrePais: 'Perú' },
      { idPais: 2, nombrePais: 'Chile' },
      { idPais: 3, nombrePais: 'México' },
    ];
    for (const p of fallback) {
      const opt = document.createElement('option');
      opt.value = p.idPais;
      opt.textContent = p.nombrePais;
      sel.appendChild(opt);
    }
  }
}
loadCountries();

// ====== Disponibilidad / resultados ======
const form = $('#search');
const grid = $('#resultsGrid');
const emptyEl = $('#resultsEmpty');
const errorEl = $('#resultsError');

function setLoading(yes) {
  yes ? form.classList.add('loading') : form.classList.remove('loading');
}

function guessRoomImage(room) {
  const tipo = (room?.tipoHabitacion || '').toLowerCase();
  if (tipo.includes('suite')) return 'https://images.unsplash.com/photo-1560066984-138dadb4c035?q=80&w=1400&auto=format&fit=crop';
  if (tipo.includes('deluxe') || tipo.includes('ejecutivo')) return 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=1400&auto=format&fit=crop';
  return 'https://images.unsplash.com/photo-1501183638710-841dd1904471?q=80&w=1400&auto=format&fit=crop';
}

function renderResults(list) {
  grid.innerHTML = '';
  emptyEl.hidden = list.length !== 0;
  errorEl.hidden = true;

  for (const item of list) {
    const card = document.createElement('article');
    card.className = 'card result-card';
    // guardamos IDs para el click
    card.dataset.hotelId = item.hotel.idHotel;
    card.dataset.habitacionId = item.habitacion.idHabitacion;

    card.innerHTML = `
      <img src="${guessRoomImage(item.habitacion)}" alt="Habitación disponible">
      <div class="body">
        <div class="pill" style="margin-bottom:8px">${item.hotel.nombreHotel || 'Hotel'}</div>
        <h3 style="margin:0 0 6px">${item.habitacion.tipoHabitacion || 'Habitación'}</h3>
        <p class="muted" style="margin:0 0 10px">${item.hotel.ciudad || ''}${item.hotel.ciudad && item.hotel.nombrePais ? ' · ' : ''}${item.hotel.nombrePais || ''}</p>
        <div class="price" style="margin-bottom:10px">${fmtMoney(item.habitacion.precioNoche)} / noche</div>
        <div class="muted" style="font-size:.9rem">Código: ${item.habitacion.codigoHabitacion || '—'} · Capacidad: ${item.habitacion.capacidad ?? '—'}</div>
      </div>
    `;

    // click → ir a reserva/reserva.html con parámetros (incluye fechas del formulario)
    card.addEventListener('click', () => {
      const formData = Object.fromEntries(new FormData(form).entries());
      const qs = new URLSearchParams({
        hotelId: card.dataset.hotelId,
        habitacionId: card.dataset.habitacionId,
        entrada: formData.entrada,
        salida: formData.salida
      }).toString();
      location.href = `reserva/reserva.html?${qs}`;
    });

    grid.appendChild(card);
  }
}


function normalizeReserva(res) {
  return {
    idReserva: res.idReserva,
    idHabitacion: res.idHabitacion ?? null,
    codigoHabitacion: res.codigoHabitacion ?? null,
    fechaEntrada: res.fechaEntrada,
    fechaSalida: res.fechaSalida,
    estadoReserva: res.estadoReserva
  };
}

const overlapsRange = (room, reservas, start, end) => {
  const rid = room.idHabitacion;
  const rcode = room.codigoHabitacion;
  for (const r of reservas) {
    const byId = r.idHabitacion && rid && Number(r.idHabitacion) === Number(rid);
    const byCode = r.codigoHabitacion && rcode && String(r.codigoHabitacion) === String(rcode);
    if (byId || byCode) {
      const aStart = parseDate(r.fechaEntrada);
      const aEnd = parseDate(r.fechaSalida);
      if (overlaps(start, end, aStart, aEnd)) return true;
    }
  }
  return false;
};

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  errorEl.hidden = true;
  emptyEl.hidden = true;
  grid.innerHTML = '';
  setLoading(true);

  const data = Object.fromEntries(new FormData(form).entries());
  const paisId = Number(data.pais || 0);
  const entrada = parseDate(data.entrada);
  const salida = parseDate(data.salida);

  if (!(paisId && data.entrada && data.salida && entrada < salida)) {
    setLoading(false);
    errorEl.hidden = false;
    errorEl.textContent = 'Selecciona un país y un rango de fechas válido (entrada < salida).';
    return;
  }

  try {
    const [hotelesRaw, habitacionesRaw, reservasRaw] = await Promise.all([
      fetchJSON(API.hoteles),
      fetchJSON(API.habitaciones),
      fetchJSON(API.reservas)
    ]);

    const hoteles = Array.isArray(hotelesRaw) ? hotelesRaw : (hotelesRaw?.data || []);
    const habitaciones = Array.isArray(habitacionesRaw) ? habitacionesRaw : (habitacionesRaw?.data || []);
    const reservas = (Array.isArray(reservasRaw) ? reservasRaw : (reservasRaw?.data || [])).map(normalizeReserva);

    // Hoteles del país seleccionado (por idPais; si tu endpoint añade nombrePais, no hace falta usarlo aquí)
    const hotelesPais = hoteles.filter(h => Number(h.idPais) === paisId);
    const hotelIds = new Set(hotelesPais.map(h => Number(h.idHotel)));

    const disponibles = [];
    for (const hab of habitaciones) {
      const hid = Number(hab.idHotel);
      if (!hotelIds.has(hid)) continue;

      // Estado (si lo manejas) y superposición de fechas
      const estado = (hab.estado || '').toLowerCase();
      const estadoOk = !estado || ['disponible','libre','open','available'].some(k => estado.includes(k));
      if (!estadoOk) continue;

      if (!overlapsRange(hab, reservas, entrada, salida)) {
        const hotel = hotelesPais.find(h => Number(h.idHotel) === hid) || {};
        disponibles.push({ hotel, habitacion: hab });
      }
    }

    renderResults(disponibles);

  } catch (err) {
    console.error(err);
    errorEl.hidden = false;
    errorEl.textContent = `Error al consultar la API: ${err.message}`;
  } finally {
    setLoading(false);
    $('#results').scrollIntoView({ behavior: 'smooth' });
  }
});

// Accesibilidad: cerrar menú al presionar Escape
document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape') mobile.style.display = 'none';
});
