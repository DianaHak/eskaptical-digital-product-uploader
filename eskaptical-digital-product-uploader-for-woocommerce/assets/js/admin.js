document.addEventListener('DOMContentLoaded', function () {
	const productInput = document.getElementById('wdpbu_product_files');
	const imageInput = document.getElementById('wdpbu_product_images');

	function updateTitle(input, originalLabel) {
		if (!input) {
			return;
		}

		const wrapper = input.closest('.wdpbu-field');
		if (!wrapper) {
			return;
		}

		const label = wrapper.querySelector('label');
		if (!label) {
			return;
		}

		const count = input.files ? input.files.length : 0;
		label.textContent = count > 0 ? `${originalLabel} (${count})` : originalLabel;
	}

	if (productInput) {
		const originalProductLabel = productInput.closest('.wdpbu-field').querySelector('label').textContent;
		productInput.addEventListener('change', function () {
			updateTitle(productInput, originalProductLabel);
		});
	}

	if (imageInput) {
		const originalImageLabel = imageInput.closest('.wdpbu-field').querySelector('label').textContent;
		imageInput.addEventListener('change', function () {
			updateTitle(imageInput, originalImageLabel);
		});
	}
});