import { addStyles } from '../../../plugins/sim-plugin/includes/js/imports.js';

console.log("library.js loaded");


function catChanged(target){
	var parentId = target.closest('.infobox').dataset.parent;
	
	var parentDiv = target.closest('.categories');
	
	if(target.checked){
		//An recipetype is just selected, find all element with its value as parent attribute
		parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
			//Make this subcategory visible
			el.classList.remove('hidden');
			
			//Show the label
			parentDiv.querySelector('#subcategorylabel').classList.remove('hidden');
		});
		
	//If we just deselected a parent category
	}else if(parentId == undefined){
		//Hide the label if there is no category visible anymore
		if(parentDiv.querySelector('.childtypes input[type="checkbox"]:checked') == null){
			parentDiv.querySelector('#subcategorylabel').classList.add('hidden');
		
			//An recipetype is just deselected, find all element with its value as parent attribute
			parentDiv.querySelectorAll("[data-parent='"+target.value+"']").forEach(el=>{
				//Make this subcategory invisible
				el.classList.add('hidden');
			});
		}
	}
}

async function addCatType(target){
	let parentDiv, parentData;
	let response	= await FormSubmit.submitForm(target, 'frontend_posting/add_category');

	if(response){
		//Get the newly added category parent id
		let parentCat  	= target.closest('form').querySelector('[name="cat_parent"]').value;
		let postType	= target.closest('form').querySelector('[name="post_type"]').value;
		let catName		= target.closest('form').querySelector('[name="cat_name"]').value;
		
		
		//Add the new category as checkbox
		let html = `
		<div class="infobox" ${parentData}>
			<input type="checkbox" class="${postType}type" id="${postType}type[]" value="${response.id}" checked>
			<label class="option-label category-select">${catName}</label>
		</div>
		`
		parentDiv.insertAdjacentHTML('afterBegin', html);
		Main.hideModals();

		Main.displayMessage(`Succesfully added the ${catName} category`);
	}
}

async function addBook(target){
	let cell			= target.closest('td');
	let row				= target.closest('tr');
	let formData		= new FormData();

	row.querySelectorAll('input, textarea').forEach(input=>formData.append(input.name, input.value));
	row.querySelectorAll('td > img').forEach(img=>formData.append('image', img.src));
	console.log(target);

	target.classList.add('hidden');
	cell.querySelector(`.loadergif_wrapper`).classList.remove('hidden');

	let response	= await FormSubmit.fetchRestApi('library/add_book', formData);
	
	if(response){
		console.log(response);

		cell.innerHTML	= response;

		Main.displayMessage(response.message);
	}else{
		target.classList.remove('hidden');
		cell.querySelector(`.loadergif_wrapper`).classList.add('hidden');
	}
}

document.addEventListener("DOMContentLoaded", function() {
	

});

document.addEventListener("click", event =>{
	let target = event.target;

	if(target.matches(`.add-book`)){
		addBook(target);
	}else if(target.matches(`.delete-book`)){
		target.remove();
	}
	
	// SHow add category modal
	if(target.classList.contains('add_cat')){
		document.getElementById('add_'+target.dataset.type+'_type').classList.remove('hidden');
	}

	if(target.matches('.add_category .form_submit')){
		addCatType(target);
	}
});