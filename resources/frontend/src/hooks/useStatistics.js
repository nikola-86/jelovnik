import { useQuery } from "@tanstack/react-query";

export const useStatistics = () => {
  const {
    data,
    isLoading,
    error,
  } = useQuery({
    queryKey: ["statistics"],
    queryFn: async () => {
      const response = await fetch("/statistics");
      if (!response.ok) {
        throw new Error("Failed to fetch statistics");
      }
      return response.json();
    },
  });

  return {
    statistics: data,
    isLoading,
    error,
  };
};

