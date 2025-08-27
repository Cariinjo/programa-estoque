const mudar= document.querySelector('#div1');
const mudar2= document.querySelector('#div2');
const mudar3= document.querySelector('#div3');

mudar.addEventListener('mouseout', () => {
    mudar.innerHTML = 'Texto alterado!';
    mudar.style.backgroundColor = 'black';
    mudar.style.color="white";
});



mudar.addEventListener('mouseover', () => {
    mudar.innerHTML = 'Texto alterado denovo!';
    mudar.style.backgroundColor = 'yellow';
    mudar.style.color="black";
});
i = 0;
mudar.addEventListener('click', () => {
        if ( i <= 10) {
            mudar.innerHTML = `Texto alterado ${i}!`;
            mudar.style.backgroundColor = 'red';
            mudar.style.color="white";
            i++;
        }
        else if (i === 11) {
            alert('o contador acabou!');
            mudar.innerHTML = 'o contador acabou!';
            mudar.style.backgroundColor = 'black';
            mudar.style.color="white";
            i=12;
        }
        else {
            overlay.style.display = 'block';
        }
    }
);