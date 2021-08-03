import $ from 'jquery';
import 'bootstrap';
import './tablemanager.js';


document.addEventListener('DOMContentLoaded', function () {
  document.querySelector('#search_form').addEventListener('submit', function (e) {
    e.preventDefault();


    let btnSpinner = createElements('span', ['spinner-border', 'spinner-border-sm'], [
      ["role", "status"],
      ['aria-hidden', "true"]
    ]);
    let formBtn = document.querySelector('button.btn-primary');
    formBtn.textContent = "";
    formBtn.setAttribute('disabled', 'disabled');
    formBtn.append(btnSpinner, "Loading...");


    let tableSpinner = createElements('span', ['visually-hidden'], [], "Loading...");
    let tableBody = document.querySelector('.materiel-container tbody');
    if (tableBody) {
      tableBody.textContent = "";
      tableBody.classList.add('spinner-border');
      tableBody.setAttribute('role', 'status');
      tableBody.append(tableSpinner);
    }
    let xhr = new XMLHttpRequest();
    xhr.onload = function (e) {
      if (this.readyState === 4) {
        if (this.status === 200) {
          let response = JSON.parse(this.response);
          if (response.status == 'success') {
            renderingHtml(response.materiels);
            $('.tablemanager').tablemanager({
              firstSort: [
                [3, 0],
                [2, 0],
                [1, 'asc']
              ],
              disable: ["last"],
              appendFilterby: true,
              dateFormat: [
                [4, "mm-dd-yyyy"]
              ],
              debug: true,
              vocabulary: {
                voc_filter_by: 'Filtrer par',
                voc_type_here_filter: 'Filtre...',
                voc_show_rows: 'Eléments par page'
              },
              pagination: true,
              showrows: [5, 10, 20, 50, 100],
              disableFilterBy: [1]
            });
          }
        } else {
          console.warn('erreur + traitement')
        }
      } else {
        console.warn('erreur + traitement');
      }
    }
    xhr.open('POST', window.location.origin + "/", true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(new FormData(e.target));
  });

  // Traitement de données reçu de l'API
  function renderingHtml(materiels) {
    let materielsContainer = document.querySelector('.materiel-container');
    let formBtn = document.querySelector('button.btn-primary');

    if (materiels.length > 0) {
      let table = createElements('table', ['table', 'table-striped', 'table-hover', 'tablemanager']);
      let th_materielId = createElements('th', [], [
        ['scope', 'col']
      ], 'materiel_id');
      let th_nomCourt = createElements('th', [], [
        ['scope', 'col']
      ], 'nom_court');
      let th_marque = createElements('th', [], [
        ['scope', 'col']
      ], 'marque');
      let th_prixPublic = createElements('th', [], [
        ['scope', 'col']
      ], 'prix_public');
      let th_referenceFabricant = createElements('th', [], [
        ['scope', 'col']
      ], 'reference_fabricant');
      let th_TypeFamille = createElements('th', [], [
        ['scope', 'col']
      ], 'Type > famille');
      let th_TypeNom = createElements('th', [], [
        ['scope', 'col']
      ], 'Type > nom');
      let th_TypeMetierNom = createElements('th', [], [
        ['scope', 'col']
      ], 'Type > metier > nom ');
      let tr_head = createElements('tr');
      tr_head.append(th_materielId, th_nomCourt, th_marque, th_prixPublic, th_referenceFabricant, th_TypeFamille, th_TypeNom, th_TypeMetierNom);
      let thead = createElements('tHead');
      thead.append(tr_head);
      table.append(thead);

      let tbody = createElements('tBody');

      materiels.forEach(materiel => {
        let td_materielId = createElements('td', [], []);
        let linkMaterielDetails = createElements('a', ['tooltiped'], [
          ['href', `${window.location.origin}/materiel/${materiel.materiel_id}`],
          ['data-bs-toggle', 'tooltip'],
          ['data-bs-placement', 'top'],
          ['title', 'Cliquez pour voir les détails de ce produit']
        ], materiel.materiel_id);
        td_materielId.append(linkMaterielDetails);
        let td_nomCourt = createElements('td', [], [], materiel.nom_court);
        let td_marque = createElements('td', [], [], materiel.marque);
        let td_prixPublic = createElements('td', [], [], materiel.prix_public);
        let td_referenceFabricant = createElements('td', [], [], materiel.reference_fabricant);
        let td_TypeFamille = createElements('td');
        if ('super-materiel' == materiel.type.famille) {
          let linkSuperMateriel = createElements('a', ['tooltiped'], [
            ['href', `${window.location.origin}/super-materiels/${materiel.materiel_id}`],
            ['data-bs-toggle', 'tooltip'],
            ['data-bs-placement', 'top'],
            ['title', 'Ce produit est lié à d\'autres produits cliquez pour voir d\'éventuelles listes']
          ], materiel.type.famille);
          td_TypeFamille.append(linkSuperMateriel);
        } else {
          td_TypeFamille.textContent = materiel.type.famille;
        }
        let td_TypeNom = createElements('td', [], [], materiel.type.nom);
        let td_TypeMetierNom = createElements('td', [], [], materiel.type.metier.nom);
        let tr_body = createElements('tr');
        tr_body.append(td_materielId, td_nomCourt, td_marque, td_prixPublic, td_referenceFabricant, td_TypeFamille, td_TypeNom, td_TypeMetierNom);
        tbody.append(tr_body);
      });
      table.append(tbody);
      formBtn.textContent = "chercher";
      formBtn.removeAttribute('disabled');
      materielsContainer.textContent = "";
      materielsContainer.append(table);
    } else {
      let p = createElements('p', ['mt-4'], [], "Pas de matériels avec les informations demandées");
      formBtn.textContent = "chercher";
      formBtn.removeAttribute('disabled');
      materielsContainer.textContent = "";
      materielsContainer.append(p);
    }
  }

  // Helper function pour créer des éléments de DOM
  function createElements(tag, cls = null, attrs = null, text = null) {
    let elm = document.createElement(tag);
    if (null != cls) {
      if (cls.length > 0) {
        for (let i = 0; i < cls.length; i++) {
          elm.classList.add(cls[i]);
        }
      }
    }
    if (null != attrs) {
      if (attrs.length > 0) {
        for (let i = 0; i < attrs.length; i++) {
          elm.setAttribute(attrs[i][0], attrs[i][1]);
        }
      }
    }
    if (null != text) {
      elm.textContent = text;
    }
    return elm;
  }
});