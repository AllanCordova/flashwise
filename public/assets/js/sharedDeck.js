async function shareDeck(deckId) {
  try {
    const response = await fetch(`/decks/${deckId}/share`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
    });

    const data = await response.json();

    if (data.success) {
      await navigator.clipboard.writeText(data.shareUrl);

      alert("Link gerado, cole em seu navegador!");
    } else {
      alert(
        "Erro ao gerar link de compartilhamento: " +
          (data.message || "Erro desconhecido")
      );
    }
  } catch (error) {
    console.error("Erro:", error);
    alert("Erro ao gerar link de compartilhamento. Tente novamente.");
  }
}
