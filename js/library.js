import { cloneNode } from "../../forms/js/form_exports.js";
import { setTableLabel, hideColumn } from "../../../plugins/sim-plugin/includes/js/table.js";

console.log("library.js loaded");

async function addBook(target){
	let cell			= target.closest('td');
	let row				= target.closest('tr');
	let formData		= new FormData();

	row.querySelectorAll('input, textarea').forEach(input => {
		if(input.type != 'checkbox' || input.checked){
			formData.append(input.name, input.value);
		}
	});

	row.querySelectorAll('td > img').forEach(img=>formData.append('image', img.src));

	target.classList.add('hidden');
	cell.querySelector(`.loader-wrapper`).classList.remove('hidden');

	let response	= await FormSubmit.fetchRestApi('library/add_book', formData);
	
	if(response){
		cell.innerHTML	= response;

		Main.displayMessage(response.message);

		row.classList.add('processed');
	}else{
		target.classList.remove('hidden');
		cell.querySelector(`.loader-wrapper`).classList.add('hidden');

		target.remove();
	}
}

let fileUploadWrap, totalFiles;
async function fileUpload(target, location){
	totalFiles 		= target.files.length;

	if (totalFiles < 0){
		return;
	}
	
	//Create a formData element
	let formData = new FormData();
	
	//Add the ajax action name
	formData.append('action', 'process_library_upload');

	formData.append('location', location);

	//Add all the files to the formData
	for (let index = 0; index < totalFiles; index++) {
		let file	= target.files[index];
		formData.append('files[]', file);
	}
	
	//AJAX request
	let request = new XMLHttpRequest();
	
	//Listen to the state changes
	request.onreadystatechange = readyStateChanged;
	
	//Listen to the upload status
	request.upload.addEventListener('progress', fileUploadProgress, false);
	
	request.open('POST', sim.ajaxUrl, true);
	
	// Show loading gif
	fileUploadWrap.querySelectorAll(".loader-wrapper").forEach(loader =>{
		loader.classList.remove('hidden');
		loader.querySelectorAll(".loader-text").forEach(el =>{
			el.textContent = "Preparing upload";
			el.classList.add('upload-message');
		});
	});
	
	//Send AJAX request
	fileUploadWrap.querySelector('.loader-text').textContent = "Uploading Picture(s)";
	document.getElementById('progress-wrapper').classList.remove('hidden');

	try{
		request.send(formData);
	} catch(e){
		console.error('Error fetching book data:', e);
		Main.displayMessage(e);
	}
}

function fileUploadProgress(e){
	if(e.lengthComputable){
		let max 		= e.total;
		let current 	= e.loaded;

		let percentage 	= (current * 100)/max;
		percentage 		= Math.round(percentage * 10) / 10
		
		document.querySelectorAll("#upload-progress").forEach(el=>el.value = percentage);
		document.querySelectorAll("#progress-percentage").forEach(el=>el.textContent	= `   ${percentage}%`);

		if(percentage >= 99){
			//Remove progress barr
			document.getElementById("progress-wrapper").classList.add('hidden');
			
			// process completed
			fileUploadWrap.querySelectorAll(".loader-text").forEach(el =>{
				//Change message text
				if (totalFiles > 1){
					el.textContent = "Processing images";
				}else{
					el.textContent = "Processing image";
				}
			});
		}
	}  
}

function readyStateChanged(e){
	let request	= e.target;
	
	//If finished
	if(request.readyState == 4){
		//Success
		if (request.status >= 200 && request.status < 400) {
			fileUploadSucces(request.responseText)
		//Error
		}else{
			console.error(request.responseText);
			Main.displayMessage(JSON.parse(request.responseText).error,'error');
		}
		
		//Hide loading gif
		document.querySelectorAll(".loader-wrapper").forEach(
			function(loader){
				loader.classList.add('hidden');
			}
		);
			
		//Clear the input
		fileUploadWrap.querySelector('input[type="file"]').value = "";
	}
}

async function fileUploadSucces(result){
	let json		= JSON.parse(result);

	if(!json.success){
		Main.displayMessage(`The files failed to process:<br>${json.data[0].message}`, 'error');

		document.querySelectorAll(`.image-selector-wrap.hidden`).forEach(el=>el.classList.remove('hidden'));

		fileUploadWrap.querySelectorAll('.image-preview').forEach(el => el.remove());

		return;
	}

	let div 		= document.createElement('div');
	div.innerHTML	= json.data;
	fileUploadWrap.prepend(div);

	Main.displayMessage("The files have been processed succesfully.", 'success', true);

	fileUploadWrap.querySelector('.image-selector-wrap').classList.remove('hidden');

	// Create a custom event so others can listen to it.
	// Used by formstable uploads
	const event = new Event('uploadfinished');
	fileUploadWrap.dispatchEvent(event);

	await fetchMetaDatas();

	// Run this only when all rows are processed
	setTableLabel();

	if(sim.hidden != undefined){
		sim.hidden.forEach(col => hideColumn(div.querySelectorAll(`.sim-table th`)[col]));
	}
}

async function fetchMetaDatas(){
	let promiseArray = [];

	fileUploadWrap.querySelectorAll('table tr').forEach(async (tr) => {
		promiseArray.push(fetchMetaData(tr));
	});

	await Promise.all(promiseArray);
}

async function fetchMetaData(tr){
	try{
		// Check if a book row
		if(tr.querySelector('.title') == null){
			return;
		}

		let title	= tr.querySelector('.title').value;

		// Only search for the first author
		let author	= tr.querySelector('.author').value.trim();
		let url     = `https://openlibrary.org/search.json?q=`+encodeURIComponent(`title:${title}`);
		if(author != ''){
			url += encodeURIComponent(` author:${author}`)+'&limit=1';
		}
		url += '&fields=key,title,author_name,subtitle,alternative_subtitle,cover_i,language,number_of_pages_median,first_publish_year,description,subjects';

		const response 	= await fetch(url);
		const data 		= await response.json();
		let bookData    = data['docs'][0] ?? [];

		// If no book data found, create a list with possible authors
		if(bookData.length == 0 && author == ''){
			let id	= `authorlist-${title.replaceAll(" ", "_").replaceAll("'", "")}`;
			tr.querySelectorAll('.author').forEach(el => el.setAttribute("list", id));

			let list = `<datalist id='${id}' class='author-selection'>`;

			data['docs'].forEach((doc) => {
				if(doc['author_name'] != undefined){
					list += `<option value='${doc['author_name'].join()}'>`;
				}
			});

			list += `</datalist>`;
			tr.querySelector('.authors').insertAdjacentHTML('afterEnd', list);
		}else{
			let authors 	= bookData['author_name'] ?? [author];
			let wrapper 	= tr.querySelector(`.authors.clone-divs-wrapper`);
			let baseElement = wrapper.querySelector(`.clone-div`);

			authors.forEach(author => {
				let clone		= cloneNode(baseElement);
				clone.querySelector('.author').value = author;

				wrapper.appendChild(clone);
			});		
			
			baseElement.remove()
		}

		let image        = bookData['cover_i'] ?? '';
        if(image != ''){
           	let  smallUrl    = `https://covers.openlibrary.org/b/id/${image}-S.jpg`;
		   	let  largeUrl    = `https://covers.openlibrary.org/b/id/${image}-L.jpg`;

			tr.querySelector('.image').innerHTML = `<input type='hidden' name='image' value='${image}'><a href='${largeUrl}' target='_blank'><img src='${smallUrl}' class='book-image' loading='lazy'></a>`;
        }

		let subtitle	= bookData['subtitle'] ?? '';

		let year		= bookData['first_publish_year'] ?? '';
		year			= year[0] ?? year;

		let language	= bookData['language'] ?? '';
		language		= language[0] ?? language;

		let pageCount		= bookData['number_of_pages'] ?? bookData['number_of_pages_median'] ?? '';

		let html = `<td><input type='text' name='subtitle' class='subtitle' value='${subtitle}'></td>`;
		html += `<td><input type='text' name='series' class='series' value='${bookData['series'] ?? ''}'></td>`;
		html += `<td><input type='text' name='year' class='year' value='${year}'></td>`;
		html += `<td><input type='text' name='language' class='language' value='${language}'></td>`;
		html += `<td><input type='text' name='pages' class='pages' value='${pageCount}'></td>`;

		let placeholder	= tr.querySelector('.placeholder');
		if(placeholder == null){
			tr.querySelector('.subtitle').value = subtitle;
			tr.querySelector('.series').value 	= bookData['series'] ?? '';
			tr.querySelector('.year').value 	= year;
			tr.querySelector('.language').value = language;
			tr.querySelector('.pages').value 	= pageCount;
		}else{
			placeholder.outerHTML = html;
		}

        let description	= tr.querySelector('.description').value
		description		= bookData['description'] != undefined ? bookData['description']['value'] != undefined ? bookData['description']['value'] : '' : '';

		let key        	= bookData['key'] ?? '';
		let workJson	= '';
        if(key != ''){
            url      = `https://openlibrary.org${key}`;
			tr.querySelector('.url').innerHTML = `
			<input type='hidden' name='url' value='${url}'>
			<a href='${url}' target='_blank'>View on Open Library</a>
			`;

			// Fetch the summary from the Open Library API
			const workResponse 	= await fetch(url+'.json');
			workJson 			= await workResponse.json();

			description 		= workJson['description'] ?? '';	
			
			description 		= description.value ?? description;

			if(	workJson['subjects'] != undefined ){
				workJson['subjects'].forEach((subject) => {
					tr.querySelectorAll(`.category-select [data-name="${subject}"]`).forEach((el) => el.checked = true);
				});
			}
        }

		if(description != ''){
			tr.querySelector('.description').value = description;
		}

		// add book automatically if there is a picture and description
		if(image != '' && key != '' && typeof(workJson) != 'undefined' && workJson['subjects'] != undefined){
			tr.querySelector('.add-book').click();
		}
	} catch(e){
		console.error('Error fetching book data:', e);
	}
}

function hideCss(type){
	let style = document.createElement('style');
	style.innerHTML = `.${type} { display: none; }`;
	document.getElementsByTagName('head')[0].appendChild(style);

	const url 		= new URL(window.location);
	url.searchParams.set('hide', type);
}

document.addEventListener("DOMContentLoaded", function() {
	

});

document.addEventListener("click", event =>{
	let target = event.target;

	if(fileUploadWrap != undefined && target.matches(`.add-books`)){
		fileUploadWrap.querySelectorAll('.image-preview, .book.table-wrapper').forEach(el => el.remove());
	}else if(target.matches(`.add-book`)){
		addBook(target);
	}else if(target.matches(`.delete-book`)){
		target.closest('tr').remove();
	}else if(target.matches('.hide-existing-books')){
		hideCss('existing-book');
		target.remove();
	}else if(target.matches('.hide-processed-books')){
		hideCss('processed');
		target.remove();
	}
});

document.addEventListener("change", async event =>{
	let target = event.target;

	if(target.name == 'image-selector' && target.files.length > 0){
		fileUploadWrap	= target.closest('.file-upload-wrap');

		// Remove the old table if any
		fileUploadWrap.querySelectorAll('.image-preview, .book.table-wrapper').forEach(el => el.remove());
		
		// Make sure we have a location
		let location	= fileUploadWrap.querySelector(`.book-location`);
		const isValid 	= location.reportValidity();
		if (!isValid) {			
			target.value = '';
			Main.displayMessage("Please select a location for the book.", 'error');
			return;
		}
		location = location.value;
		
		// Remove previous result tables
		document.querySelectorAll('.book-table-wrapper').forEach(el => el.remove());
		
		target.closest('.image-selector-wrap').classList.add('hidden');
		fileUploadWrap.querySelectorAll('.image-preview').forEach(el => el.remove());
		
		for (const file of target.files) {
			let reader = new FileReader();

			reader.onload = function(e) {
				let div 			= document.createElement('div');
				div.classList.add('image-preview');
				div.style.textAlign	= 'center';

				let img 			= document.createElement('img');
				img.src 			= e.target.result;
				img.classList.add('book-image');
				img.style.maxHeight	= "100px";
				img.style.padding	=  "0";

				div.appendChild(img);
				
				fileUploadWrap.prepend(div);
			}
			reader.readAsDataURL(file);

			fileUpload(target, location);
		}
	}else if(target.matches('.title, .author')){
		let tr = target.closest('tr');

		tr.style.position = 'relative';

		let loader = Main.showLoader(tr.lastChild, false, tr.offsetHeight - 100, 'Updating book data...');

		loader.style.position 			= 'absolute';
		loader.style.top				= 0;
		loader.style.left				= 0;
		loader.style.width				= '100vw';
		loader.style.height				= '100%';
		loader.style.backgroundColor	= 'rgba(0, 0, 0, 0.5)';
		loader.style.color				= 'white';
		loader.style.display			= 'flex';
		loader.style.justifyContent		= 'center';
		loader.style.alignItems			= 'center';
		
		// Update metadata for the book row
		await fetchMetaData(tr);

		loader.remove();
	}
});