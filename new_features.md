# new features

## Backlog
TODO connecter my cardio/ watch 
TODO : Picture density training

### new feature
export img from SVG load training
https://jsfiddle.net/zvma7oLt/3/

```
const createStyleElementFromCSS = () => {
  // JSFiddle's custom CSS is defined in the second stylesheet file
  const sheet = document.styleSheets[1];

  const styleRules = [];
  for (let i = 0; i < sheet.cssRules.length; i++)
    styleRules.push(sheet.cssRules.item(i).cssText);

  const style = document.createElement('style');
  style.type = 'text/css';
  style.appendChild(document.createTextNode(styleRules.join(' ')))

  return style;
};
const style = createStyleElementFromCSS();

const download = () => {
  // fetch SVG-rendered image as a blob object
  const svg = document.querySelector('svg');
  svg.insertBefore(style, svg.firstChild); // CSS must be explicitly embedded
  const data = (new XMLSerializer()).serializeToString(svg);
  const svgBlob = new Blob([data], {
    type: 'image/svg+xml;charset=utf-8'
  });
	style.remove(); // remove temporarily injected CSS

  // convert the blob object to a dedicated URL
  const url = URL.createObjectURL(svgBlob);

  // load the SVG blob to a flesh image object
  const img = new Image();
  img.addEventListener('load', () => {
    // draw the image on an ad-hoc canvas
    const bbox = svg.getBBox();

    const canvas = document.createElement('canvas');
    canvas.width = bbox.width;
    canvas.height = bbox.height;

    const context = canvas.getContext('2d');
    context.drawImage(img, 0, 0, bbox.width, bbox.height);

    URL.revokeObjectURL(url);

    // trigger a synthetic download operation with a temporary link
    const a = document.createElement('a');
    a.download = 'image.png';
    document.body.appendChild(a);
    a.href = canvas.toDataURL();
    a.click();
    a.remove();
  });
  img.src = url;
};
```


## DONE
DONE : prendre en compte le poids Strava
DONE : afficher ma vitesse et ftp en fonction de mon poids pendant la seance. 
DONE : visualisation en liste - afficher les tronçons de la séance et la durée des tronçons faits / à venir
DONE : temps restant dans la phase
DONE : Ajouter button pause
DONE : Purge localstorage


