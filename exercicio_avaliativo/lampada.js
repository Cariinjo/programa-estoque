const imagem4 = document.createElement("img");
imagem4.setAttribute('src', 'acesa.jpg');
imagem4.setAttribute('alt', 'Exemplo 1');
imagem4.style.width = "200px";
imagem4.style.height = "200px";
document.getElementById("apagador").appendChild(imagem4);

let acesa = false
const alterando = document.getElementById('mudar')
alterando.addEventListener('click', () => {
      if (acesa) {
        imagem4.src = "apagada.jpg";
        acesa = false;
      } else {
        imagem4.src = "acesa.jpg";
        acesa = true;
      }})