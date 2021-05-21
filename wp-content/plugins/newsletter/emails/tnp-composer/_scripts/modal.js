const TNPModal = function (options) {
    'use strict'

    const _options = {
        title: '',
        content: '',
        contentSelector: '',
        showClose: true,
        onClose: null,
        closeWhenClickOutside: true,
        confirmText: 'CONFIRM',
        confirmClassName: 'button',
        showConfirm: false,
        onConfirm: null,
        clickConfirmOnPressEnter: false,
        style: null,
        ...options
    };

    let _modalElement = null;
    let _modalContainer = null;
    let _closeElement = null;
    let _contentElement = null;
    let _isClosing = false;

    const open = () => {
        if (_modalElement === null) {
            //render element
            _render();
        }
        return _contentElement;
    }

    const close = () => {

        if (!_isClosing) {
            _modalElement.addEventListener('animationend', function () {
                document.body.removeChild(_modalElement);
                destroyDOMElements();
                _isClosing = false;
            });

            _modalContainer.className = _modalContainer.className + ' on-close';
            _modalElement.className = _modalElement.className + ' on-close';

            if (_options.onClose) {
                _options.onClose();
            }
            _isClosing = true;
        }

    }

    const destroyDOMElements = () => {
        if (_contentElement) {
            _contentElement.style.display = 'none';
            document.body.appendChild(_contentElement);
        }
        _modalElement = null;
        _modalContainer = null;
        _closeElement = null;
        _contentElement = null;
    }

    const onConfirm = () => {

        if (_options.onConfirm) {
            _options.onConfirm();
        }

        close();
    }

    const _addTitle = (title) => {
        const titleElement = document.createElement('h2');
        titleElement.className = 'tnp-modal-title';
        titleElement.innerText = title;

        _modalContainer.appendChild(titleElement);
    }

    const _addCloseButton = () => {
        const closeEl = document.createElement('div');
        closeEl.className = 'tnp-modal-close';
        closeEl.innerText = '×';

        _modalContainer.appendChild(closeEl);

        closeEl.addEventListener('click', function (e) {
            e.stopPropagation();
            close();
        });
    }

    const _render = () => {

        _modalContainer = document.createElement('div');
        _modalContainer.className = 'tnp-modal-container';

        if (_options.title && _options.title.length > 0) {

            _addTitle(_options.title);

        }

        if (_options.content && _options.content.length > 0) {

            _contentElement = document.createElement('div');
            _contentElement.className = 'tnp-modal-content';
            _contentElement.innerHTML = _options.content;
            _modalContainer.appendChild(_contentElement);

        } else if (_options.contentSelector && _options.contentSelector.length > 0) {

            _contentElement = document.querySelector(_options.contentSelector);
            _contentElement.style.display = _contentElement.style.display === 'none' ? 'block' : _contentElement.style.display;
            _modalContainer.appendChild(_contentElement);

        } else {

            _contentElement = document.createElement('div');
            _contentElement.className = 'tnp-modal-content';
            _modalContainer.appendChild(_contentElement);

        }

        if (_options.showClose) {
            _addCloseButton();
        }

        if (_options.showConfirm) {

            const confirmContainerEl = document.createElement('div');
            confirmContainerEl.className = 'tnp-modal-confirm';

            const confirmEl = document.createElement('button');
            confirmEl.className = _options.confirmClassName || 'button-secondary';
            confirmEl.innerText = _options.confirmText || 'CONFIRM';

            confirmEl.addEventListener('click', onConfirm);

            if (_options.clickConfirmOnPressEnter) {
                document.addEventListener('keyup', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        confirmEl.click();
                    }
                })
            }

            confirmContainerEl.appendChild(confirmEl);
            _modalContainer.appendChild(confirmContainerEl);

        }

        if (_options.style) {
            for (const _styleProperty in _options.style) {
                if (_modalContainer.style && typeof (_modalContainer.style[_styleProperty]) !== "undefined") {
                    _modalContainer.style[_styleProperty] = _options.style[_styleProperty];
                }
            }
        }

        if (_options.backgroundColor) {
            _modalContainer.style.backgroundColor = _options.backgroundColor;
        }

        if (_options.height) {
            _modalContainer.style.height = _options.backgroundColor;
        }


        _modalElement = document.createElement('div');
        _modalElement.className = 'tnp-modal open';

        if (_options.closeWhenClickOutside) {
            //Close modal if clicked outside modal
            _modalElement.addEventListener('click', function (event) {
                if (!event.target.closest('.' + _modalContainer.className)) {
                    close();
                }
            });
        }

        _modalElement.appendChild(_modalContainer);
        document.body.appendChild(_modalElement);

    }

    if (_options.triggerSelector && _options.triggerSelector.length > 0) {
        const _triggerElement = document.querySelector(_options.triggerSelector);
        _triggerElement.addEventListener('click', open);
    }

    return {
        open,
        close
    }

};

window.TNPModal = TNPModal;
