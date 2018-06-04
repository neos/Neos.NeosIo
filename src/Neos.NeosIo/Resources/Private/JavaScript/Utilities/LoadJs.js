export default src => new Promise(resolve => {
	const script = document.createElement('script');

	script.setAttribute('type', 'text/javascript');
	script.setAttribute('src', src);
	script.onload = () => resolve();

	document.getElementsByTagName('head')[0].appendChild(script);
});
