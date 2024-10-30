(function (data) {
  var modalContent = new tingle.modal({ type: data['type'] });
  var modalButton = document.querySelector(data['button_class']);
  modalButton.addEventListener('click', function () {
    modalContent.open();
  });

  modalContent.setContent(document.querySelector('.wordpress-hmw')).outerHTML();
})(tingleData);

