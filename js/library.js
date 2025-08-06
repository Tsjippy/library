console.log("library.js loaded");

async function addBook(target){
	let cell			= target.closest('td');
	let row				= target.closest('tr');
	let formData		= new FormData();

	row.querySelectorAll('input, textarea').forEach(input=>formData.append(input.name, input.value));
	row.querySelectorAll('td > img').forEach(img=>formData.append('image', img.src));

	target.classList.add('hidden');
	cell.querySelector(`.loadergif_wrapper`).classList.remove('hidden');

	let response	= await FormSubmit.fetchRestApi('library/add_book', formData);
	
	if(response){
		cell.innerHTML	= response;

		Main.displayMessage(response.message);
	}else{
		target.classList.remove('hidden');
		cell.querySelector(`.loadergif_wrapper`).classList.add('hidden');

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
	
	//Show loading gif
	fileUploadWrap.querySelectorAll(".loadergif_wrapper").forEach(loader =>{
		loader.classList.remove('hidden');
		loader.querySelectorAll(".uploadmessage").forEach(el =>{
			el.textContent = "Preparing upload";
			el.classList.add('upload-message');
		});
	});
	
	//Send AJAX request
	fileUploadWrap.querySelector('.uploadmessage').textContent = "Uploading Picture(s)";
	document.getElementById('progress-wrapper').classList.remove('hidden');

	request.send(formData);
}

function fileUploadProgress(e){
	if(e.lengthComputable){
		let max 		= e.total;
		let current 	= e.loaded;

		let percentage 	= (current * 100)/max;
		percentage 		= Math.round(percentage*10)/10
		
		document.querySelectorAll("#upload_progress").forEach(el=>el.value = percentage);
		document.querySelectorAll("#progress_percentage").forEach(el=>el.textContent	= `   ${percentage}%`);

		if(percentage >= 99){
			//Remove progress barr
			document.getElementById("progress-wrapper").classList.add('hidden');
			
			// process completed
			fileUploadWrap.querySelectorAll(".uploadmessage").forEach(el =>{
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
		document.querySelectorAll(".loadergif_wrapper").forEach(
			function(loader){
				loader.classList.add('hidden');
			}
		);
			
		//Clear the input
		fileUploadWrap.querySelector('input[type="file"]').value = "";
	}
}

function fileUploadSucces(result){
	fileUploadWrap.innerHTML	= JSON.parse(result).data + fileUploadWrap.innerHTML;
	
	Main.displayMessage("The files have been processed succesfully.", 'success', true);

	fileUploadWrap.querySelector('.image-selector-wrap').classList.remove('hidden');

	// Create a custom event so others can listen to it.
	// Used by formstable uploads
	const event = new Event('uploadfinished');
	fileUploadWrap.dispatchEvent(event);

	fetchMetaDatas();
}

async function fetchMetaDatas(){
	fileUploadWrap.querySelectorAll('table tr').forEach(async (tr) => {
		fetchMetaData(tr);
	});
}

async function fetchMetaData(tr){
	try{
		// Check if a book row
		if(tr.querySelector('.title') == null){
			return;
		}

		let title	= tr.querySelector('.title').value;

		// Only search for the first author
		let author	= tr.querySelector('.author').value.split(', ')[0].split(' and ')[0].split('/')[0].split('with')[0].trim();
		let url     = `https://openlibrary.org/search.json?q=`+encodeURIComponent(`title:${title}`);
		if(author != ''){
			url += encodeURIComponent(` author:${author}`)+'&limit=1';
		}
		url += '&fields=key,title,author_name,subtitle,alternative_subtitle,cover_i,isbn,language,number_of_pages_median,first_publish_year,description';

		const response 	= await fetch(url);
		const data 		= await response.json();
		let bookData    = data['docs'][0] ?? [];

		if(author == ''){
			let id	= `authorlist-${title.replaceAll(" ", "_").replaceAll("'", "")}`;
			tr.querySelector('.author').setAttribute("list", id);

			let list = `<datalist id='${id}' class='author-selection'>`;

			data['docs'].forEach((doc) => {
				if(doc['author_name'] != undefined){
					list += `<option value='${doc['author_name'].join()}'>`;
				}
			});

			list += `</datalist>`;
			tr.querySelector('.author').insertAdjacentHTML('afterEnd', list);
		}else{
			let authors 	= bookData['author_name'] ?? [author];
			authors.forEach(author => {
				});

			if(typeof(author) === 'object'){
				author	= author.jo
			}else{
				console.error(author);
				tr.querySelector('.author').value = author;
				}
			
		}

		let image        = bookData['cover_i'] ?? '';
        if(image != ''){
           	let  smallUrl    = `https://covers.openlibrary.org/b/id/${image}-S.jpg`;
		   	let  largeUrl    = `https://covers.openlibrary.org/b/id/${image}-L.jpg`;

			tr.querySelector('.image').innerHTML = `<input type='hidden' name='image' value='${image}'><a href='${largeUrl}' target='_blank'><img src='${smallUrl}' class='book-image' loading='lazy'></a>`;
        }

		let subtitle	= bookData['subtitle'] ?? '';

		let isbn		= JSON.stringify(bookData['isbn'] ?? '');

		let year		= bookData['first_publish_year'] ?? '';
		year			= year[0] ?? year;

		let language	= bookData['language'] ?? '';
		language		= language[0] ?? language;

		let pageCount		= bookData['number_of_pages'] ?? bookData['number_of_pages_median'] ?? '';

		let html = `<td><input type='text' name='subtitle' class='subtitle' value='${subtitle}'></td>`;
		html += `<td class='hidden'><input type='text' name='isbn' class='isbn' value='${isbn}'></td>`;
		html += `<td><input type='text' name='series' class='series' value='${bookData['series'] ?? ''}'></td>`;
		html += `<td><input type='text' name='year' class='year' value='${year}'></td>`;
		html += `<td><input type='text' name='language' class='language' value='${language}'></td>`;
		html += `<td><input type='text' name='pages' class='pages' value='${pageCount}'></td>`;

		let placeholder	= tr.querySelector('.placeholder');
		if(placeholder == null){
			tr.querySelector('.subtitle').value = subtitle;
			tr.querySelector('.isbn').value 	= isbn;
			tr.querySelector('.series').value 	= bookData['series'] ?? '';
			tr.querySelector('.year').value 	= year;
			tr.querySelector('.language').value = language;
			tr.querySelector('.pages').value 	= pageCount;
		}else{
			placeholder.outerHTML = html;
		}

        let summary      	= tr.querySelector('.summary').value
		summary				= bookData['description'] != undefined ? bookData['description']['value'] != undefined ? bookData['description']['value'] : summary : summary;

		let key        = bookData['key'] ?? '';
        if(key != ''){
            url      = `https://openlibrary.org${key}`;
			tr.querySelector('.url').innerHTML = `<a href='${url}' target='_blank'>View on Open Library</a>`;

			// Fetch the summary if not already set or needs to be updated
			if(summary == '' || placeholder == null){
				// Fetch the summary from the Open Library API
				const descriptionResponse 	= await fetch(url+'.json');
				const description 			= await descriptionResponse.json();

				summary = description['description'] ?? '';	
				
				summary = summary.value ?? summary;
			}
        }

		if(summary != ''){
			tr.querySelector('.summary').value = summary;
		}

	} catch(e){
		console.error('Error fetching book data:', e);
	}
}

document.addEventListener("DOMContentLoaded", function() {
	

});

document.addEventListener("click", event =>{
	let target = event.target;

	if(target.matches(`.add-book`)){
		addBook(target);
	}else if(target.matches(`.delete-book`)){
		target.closest('tr').remove();
	}
});

document.addEventListener("change", async event =>{
	let target = event.target;

	if(target.name == 'image-selector' && target.files.length > 0){
		fileUploadWrap	= target.closest('.file_upload_wrap');
		
		// Make sure we have a location
		let location	= fileUploadWrap.querySelector(`.book-location`);
		const isValid 	= location.reportValidity();
		if (!isValid) {
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

				let img 			= document.createElement('img');
				img.src 			= e.target.result;
				img.classList.add('book-image');
				img.style.maxHeight	= "100px";

				div.appendChild(img);
				
				fileUploadWrap.prepend(div);
			}
			reader.readAsDataURL(file);

			fileUpload(target, location);
		}
	}else if(target.matches('.title, .author')){
		let tr = target.closest('tr');
		
		// Update metadata for the book row
		await fetchMetaData(tr);

		Main.displayMessage("Updated details succesfully.", 'success', true);
	}
});