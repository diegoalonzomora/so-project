const $ = (sel) => document.querySelector(sel);
const $$ = (sel) => Array.from(document.querySelectorAll(sel));

const API_BASE = new URL('./api/v1/', window.location.href).href;

const API = {
  hoteles: new URL('hotel.php', API_BASE).href,
  habitaciones: new URL('habitacion.php', API_BASE).href,
  paises: new URL('pais.php', API_BASE).href,
};

const fmtMoney = (n) =>
  typeof n === 'number' || (typeof n === 'string' && n.trim() !== '')
    ? `S/ ${Number(n).toFixed(2)}`
    : 'S/ —';

$('#year').textContent = new Date().getFullYear();

const hamb = $('#hamb');
const mobile = $('#mobile');
hamb.addEventListener('click', ()=>{
  const show = mobile.style.display === 'block';
  mobile.style.display = show ? 'none' : 'block';
});
window.addEventListener('resize', ()=>{
  if (window.innerWidth > 720) mobile.style.display = 'none';
});

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

let todasHabitaciones = [];
let todosHoteles = [];
let ciudadesUnicas = new Set();

async function cargarDatosIniciales() {
  try {
    const [hotelesData, habitacionesData] = await Promise.all([
      fetchJSON(API.hoteles),
      fetchJSON(API.habitaciones)
    ]);

    todosHoteles = Array.isArray(hotelesData) ? hotelesData : (hotelesData?.data || []);
    todasHabitaciones = Array.isArray(habitacionesData) ? habitacionesData : (habitacionesData?.data || []);

    todosHoteles.forEach(hotel => {
      if (hotel.ciudad) ciudadesUnicas.add(hotel.ciudad);
    });

    const ciudadSelect = $('#ciudadFilter');
    Array.from(ciudadesUnicas).sort().forEach(ciudad => {
      const opt = document.createElement('option');
      opt.value = ciudad;
      opt.textContent = ciudad;
      ciudadSelect.appendChild(opt);
    });

    $('#totalHabitaciones').textContent = todasHabitaciones.length;
    $('#totalCiudades').textContent = ciudadesUnicas.size;

    mostrarHabitaciones(todasHabitaciones);

  } catch (error) {
    console.error('Error cargando datos:', error);
    $('#loadingHabitaciones').style.display = 'none';
    $('#errorHabitaciones').style.display = 'block';
  }
}

// ====== Función para obtener imagen según tipo ======
function guessRoomImage(room) {
  const tipo = (room?.tipoHabitacion || '').toLowerCase();
  if (tipo.includes('suite') || tipo.includes('presidencial')) 
    return 'https://images.unsplash.com/photo-1560066984-138dadb4c035?q=80&w=1400&auto=format&fit=crop';
  if (tipo.includes('deluxe') || tipo.includes('ejecutivo')) 
    return 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?q=80&w=1400&auto=format&fit=crop';
  if (tipo.includes('triple') || tipo.includes('familiar'))
    return 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?q=80&w=1400&auto=format&fit=crop';
  return 'https://images.unsplash.com/photo-1501183638710-841dd1904471?q=80&w=1400&auto=format&fit=crop';
}

function mostrarHabitaciones(habitaciones) {
  const grid = $('#habitacionesGrid');
  const loading = $('#loadingHabitaciones');
  const empty = $('#emptyHabitaciones');
  const error = $('#errorHabitaciones');

  loading.style.display = 'none';
  error.style.display = 'none';
  empty.style.display = 'none';
  grid.innerHTML = '';

  if (habitaciones.length === 0) {
    empty.style.display = 'block';
    return;
  }

  habitaciones.forEach(hab => {
    const hotel = todosHoteles.find(h => Number(h.idHotel) === Number(hab.idHotel)) || {};

    const card = document.createElement('article');
    card.className = 'card room';
    card.style.cursor = 'pointer';
    card.style.transition = 'transform 0.2s, box-shadow 0.2s';
    
    card.innerHTML = `
      <img src="${guessRoomImage(hab)}" alt="${hab.tipoHabitacion || 'Habitación'}" style="height: 220px; object-fit: cover">
      <div class="body">
        <div class="pill" style="margin-bottom: 8px">${hotel.nombreHotel || 'Hotel'}</div>
        <h3 style="margin: 0 0 8px">${hab.tipoHabitacion || 'Habitación'}</h3>
        <p class="muted" style="margin: 0 0 12px; font-size: 0.9rem">
          📍 ${hotel.ciudad || ''}
          ${hab.capacidad ? `· 👥 ${hab.capacidad} ${hab.capacidad > 1 ? 'personas' : 'persona'}` : ''}
        </p>
        <div style="display: flex; justify-content: space-between; align-items: center">
          <div class="price">${fmtMoney(hab.precioNoche)}<span style="font-size: 0.8rem; font-weight: 400"> / noche</span></div>
          <button class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem">Reservar</button>
        </div>
      </div>
    `;

    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-4px)';
      card.style.boxShadow = '0 12px 40px rgba(0,0,0,0.3)';
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0)';
      card.style.boxShadow = '';
    });

    card.addEventListener('click', (e) => {
      if (e.target.tagName !== 'BUTTON') {
        card.querySelector('button').click();
        return;
      }
      
      const qs = new URLSearchParams({
        hotelId: hotel.idHotel,
        habitacionId: hab.idHabitacion
      }).toString();
      location.href = `reserva/reserva.html?${qs}`;
    });

    grid.appendChild(card);
  });
}

$('#filtrosForm').addEventListener('submit', (e) => {
  e.preventDefault();

  const formData = Object.fromEntries(new FormData(e.target).entries());
  const ciudadFiltro = formData.ciudad;
  const capacidadFiltro = formData.capacidad;
  const precioFiltro = formData.precio;

  let habitacionesFiltradas = [...todasHabitaciones];

  if (ciudadFiltro) {
    const hotelesEnCiudad = todosHoteles
      .filter(h => h.ciudad === ciudadFiltro)
      .map(h => Number(h.idHotel));
    
    habitacionesFiltradas = habitacionesFiltradas.filter(hab => 
      hotelesEnCiudad.includes(Number(hab.idHotel))
    );
  }

  if (capacidadFiltro) {
    const capacidad = Number(capacidadFiltro);
    habitacionesFiltradas = habitacionesFiltradas.filter(hab => 
      Number(hab.capacidad) >= capacidad
    );
  }

  // Filtrar por rango de precio
  if (precioFiltro) {
    const [min, max] = precioFiltro.split('-').map(Number);
    habitacionesFiltradas = habitacionesFiltradas.filter(hab => {
      const precio = Number(hab.precioNoche);
      return precio >= min && precio <= max;
    });
  }

  const hasFilters = ciudadFiltro || capacidadFiltro || precioFiltro;
  $('#habitacionesSubtitle').textContent = hasFilters 
    ? `${habitacionesFiltradas.length} habitaciones encontradas`
    : 'Explora todas nuestras opciones';

  $('#limpiarFiltrosBtn').style.display = hasFilters ? 'inline-block' : 'none';

  mostrarHabitaciones(habitacionesFiltradas);

  $('#habitaciones').scrollIntoView({ behavior: 'smooth' });
});

$('#limpiarFiltrosBtn').addEventListener('click', () => {
  $('#filtrosForm').reset();
  $('#limpiarFiltrosBtn').style.display = 'none';
  $('#habitacionesSubtitle').textContent = 'Explora todas nuestras opciones';
  mostrarHabitaciones(todasHabitaciones);
});

document.addEventListener('click', (e) => {
  if (e.target.id === 'resetFiltersBtn') {
    $('#filtrosForm').reset();
    $('#limpiarFiltrosBtn').style.display = 'none';
    $('#habitacionesSubtitle').textContent = 'Explora todas nuestras opciones';
    mostrarHabitaciones(todasHabitaciones);
  }
});

cargarDatosIniciales();

document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape') mobile.style.display = 'none';
});
