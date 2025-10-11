// filter radios
const radios = document.querySelectorAll('input[name="filter"]');
radios.forEach(r => {
  r.addEventListener('change', () => {
    console.log('Đã chọn filter:', r.value);
    // Bạn có thể lọc dữ liệu thật ở đây
  });
});

// search
const btn = document.getElementById('searchBtn');
const input = document.getElementById('searchInput');
btn.addEventListener('click', () => {
  const kw = input.value.trim();
  if(kw) alert('Tìm kiếm: ' + kw);
});

// enter key search
input.addEventListener('keypress', e=>{
  if(e.key === 'Enter'){btn.click();}
});
