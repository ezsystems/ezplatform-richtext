import IconView from '@ckeditor/ckeditor5-ui/src/icon/iconview';

export default class IbexaIconView extends IconView {
    render() {
        const svgElement = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        const useElement = document.createElementNS('http://www.w3.org/2000/svg', 'use');

        useElement.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', this.content);

        svgElement.appendChild(useElement);

        svgElement.classList.add('ck', 'ck-icon', 'ck-button__icon');
        svgElement.setAttribute('viewBox', '0 0 20 20');

        this.element = svgElement;
    }
}
