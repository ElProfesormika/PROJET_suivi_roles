(function () {
    var depart = document.getElementById('km_depart');
    var retour = document.getElementById('km_retour');
    var distance = document.getElementById('distance-live');
    var vehicle = document.getElementById('vehicule_id');
    var hint = document.getElementById('km-hint');
    var dateDepart = document.getElementById('date_mission');
    var dateArrivee = document.getElementById('date_arrivee');

    if (dateDepart && dateArrivee) {
        dateDepart.addEventListener('change', function () {
            if (dateArrivee.value === '' || dateArrivee.value < dateDepart.value) {
                dateArrivee.value = dateDepart.value;
            }
        });
    }

    var syncVehicle = function () {
        if (!vehicle || !depart || !retour) {
            return;
        }

        var option = vehicle.options[vehicle.selectedIndex];
        var type = option ? (option.getAttribute('data-type') || '') : '';
        var lastKm = option ? option.getAttribute('data-km') : '';
        var bike = type.toLowerCase() === 'velo';

        depart.disabled = bike;
        retour.disabled = bike;
        depart.required = type !== '' && !bike;
        retour.required = type !== '' && !bike;

        if (bike) {
            depart.value = '';
            retour.value = '';
        }

        if (hint) {
            if (bike) {
                hint.textContent = 'Vélo : aucun compteur n\'est relevé à la prise ni à la restitution.';
            } else if (lastKm) {
                hint.textContent = 'Dernier kilométrage connu à la restitution : ' + Number(lastKm).toLocaleString('fr-FR') + ' km. Relevez le compteur à la prise, puis à la restitution.';
            } else if (type !== '') {
                hint.textContent = 'Relevez le compteur à la prise du véhicule, puis à sa restitution.';
            } else {
                hint.textContent = 'Le kilométrage est relevé à la prise du véhicule, puis à sa restitution.';
            }
        }

        if (typeof updateDistance === 'function') {
            updateDistance();
        }
    };

    var updateDistance = function () {
        if (!depart || !retour || !distance) {
            return;
        }

        if (depart.disabled) {
            distance.textContent = 'Distance : sans compteur';
            distance.classList.remove('text-danger');
            return;
        }

        var kmDepart = parseInt(depart.value, 10);
        var kmRetour = parseInt(retour.value, 10);

        if (Number.isNaN(kmDepart) || Number.isNaN(kmRetour)) {
            distance.textContent = 'Distance : --';
            distance.classList.remove('text-danger');
            return;
        }

        var ecart = kmRetour - kmDepart;
        if (ecart < 0) {
            distance.textContent = 'Distance : le kilométrage à la restitution est inférieur à la prise';
            distance.classList.add('text-danger');
            return;
        }

        distance.classList.remove('text-danger');
        distance.textContent = 'Distance : ' + ecart.toLocaleString('fr-FR') + ' km';
    };

    if (depart && retour) {
        depart.addEventListener('input', updateDistance);
        retour.addEventListener('input', updateDistance);
    }

    if (vehicle) {
        vehicle.addEventListener('change', syncVehicle);
        syncVehicle();
    } else {
        updateDistance();
    }

    var addButton = document.getElementById('add-passager');
    var list = document.getElementById('passagers-list');

    if (!addButton || !list) {
        return;
    }

    var rowHtml = function (index) {
        return ''
            + '<div class="col-sm-5"><input type="text" class="form-control" name="passagers[' + index + '][nom]" placeholder="Nom" maxlength="100"></div>'
            + '<div class="col-sm-5"><input type="text" class="form-control" name="passagers[' + index + '][prenom]" placeholder="Prénom" maxlength="100"></div>'
            + '<div class="col-sm-2"><button type="button" class="btn btn-outline-secondary w-100 remove-passager">Retirer</button></div>';
    };

    addButton.addEventListener('click', function () {
        var index = list.querySelectorAll('.passager-row').length;
        var row = document.createElement('div');
        row.className = 'row g-2 passager-row mb-2';
        row.innerHTML = rowHtml(index);
        list.appendChild(row);
    });

    list.addEventListener('click', function (event) {
        var button = event.target.closest('.remove-passager');
        if (!button) {
            return;
        }

        var rows = list.querySelectorAll('.passager-row');
        var row = button.closest('.passager-row');
        if (!row) {
            return;
        }

        if (rows.length === 1) {
            row.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
            return;
        }

        row.remove();
    });
})();
