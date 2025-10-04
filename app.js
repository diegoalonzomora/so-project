// Año dinámico
document.getElementById('year').textContent = new Date().getFullYear();

// Menú móvil
const hamb = document.getElementById('hamb');
const mobile = document.getElementById('mobile');
hamb.addEventListener('click', ()=>{
  const show = mobile.style.display === 'block';
  mobile.style.display = show ? 'none' : 'block';
});
window.addEventListener('resize', ()=>{
  if (window.innerWidth > 720) mobile.style.display = 'none';
});

// Scroll suave para enlaces internos
document.querySelectorAll('a[href^="#"]').forEach(a=>{
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

// Buscador demo (simula redirección)
document.getElementById('search').addEventListener('submit', (e)=>{
  e.preventDefault();
  const data = Object.fromEntries(new FormData(e.currentTarget).entries());
  const params = new URLSearchParams(data).toString();
  alert(`Buscando disponibilidad en: ${data.destino}\nEntrada: ${data.entrada}\nSalida: ${data.salida}`);
  // location.href = `/resultados?${params}`;
});

// Accesibilidad: cerrar menú al presionar Escape
document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape') mobile.style.display = 'none';
});
