import React, { useEffect, useState } from 'react';
import SearchModal from './SearchModal';

const GlobalSearch = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);

    const handleInputClick = () => {
        setIsModalOpen(true);
    };

    useEffect(() => {
        const globalSearchInput = document.getElementById('globalsearchinput');

        if (globalSearchInput) {
            globalSearchInput.addEventListener('click', handleInputClick);

            return () => {
                globalSearchInput.removeEventListener('click', handleInputClick);
            };
        }
    }, []);


    return (
        <SearchModal
            isOpen={isModalOpen}
            onClose={() => setIsModalOpen(false)}
        />
    );
};

export default GlobalSearch;