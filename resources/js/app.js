import './bootstrap';

import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';


document.addEventListener('click', e => {

    if(
        e.target.matches('[data-confirm]') &&
        !confirm(e.target.dataset.confirm)
    ){
        e.preventDefault();
    }

});