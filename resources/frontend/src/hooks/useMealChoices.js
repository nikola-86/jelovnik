import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";

export const useMealChoices = () => {
  const queryClient = useQueryClient();

  const {
    data,
    isLoading,
    error,
  } = useQuery({
    queryKey: ["mealChoices"],
    queryFn: async () => {
      const response = await fetch("/meal-choices");
      if (!response.ok) {
        throw new Error("Failed to fetch meal choices");
      }
      return response.json();
    },
  });

  const uploadMutation = useMutation({
    mutationFn: async (file) => {
      const formData = new FormData();
      formData.append("file", file);

      const response = await fetch("/upload", {
        method: "POST",
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || "Upload failed");
      }

      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["mealChoices"] });
      queryClient.invalidateQueries({ queryKey: ["statistics"] });
    },
  });

  return {
    mealChoices: data ?? [],
    isLoading,
    error,
    uploadFile: uploadMutation.mutate,
    isUploading: uploadMutation.isPending,
    uploadError: uploadMutation.error,
  };
};
