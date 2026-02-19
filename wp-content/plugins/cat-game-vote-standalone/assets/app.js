(function () {
  const params = new URLSearchParams(window.location.search);
  const error = params.get('catgame_error');
  if (error) {
    console.warn('CatGame warning:', error);
  }
})();
