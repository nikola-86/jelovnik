import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import FileUpload from './components/FileUpload';
// import Statistics from './components/Statistics';
import MealChoicesTable from './components/MealChoicesTable';

const queryClient = new QueryClient();

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <div className="app">
        <h1>🍽️ Jelovnik - Meal Choices</h1>
        <FileUpload />
        {/* <Statistics /> */}
        <MealChoicesTable />
      </div>
    </QueryClientProvider>
  );
}

export default App;

