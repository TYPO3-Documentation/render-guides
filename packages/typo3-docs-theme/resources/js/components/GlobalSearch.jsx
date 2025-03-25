import React, { useEffect, useState } from 'react';
import SearchModal from './SearchModal';

const GlobalSearch = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);

    const handleInputClick = () => {
        setIsModalOpen(true);
    };

    const handleButtonClick = (e) => {
        e.preventDefault();
        setIsModalOpen(true);
    };

    useEffect(() => {
        let globalSearchInput = document.getElementById('globalsearchinput');
        let globalSearchButton = document.querySelector('#global-search-form button');

        if (!globalSearchInput) {
            globalSearchInput = document.getElementById('searchinput');
        }

        if (globalSearchInput) {
            globalSearchInput.addEventListener('click', handleInputClick);
        }

        if (globalSearchButton) {
            globalSearchButton.addEventListener('click', handleButtonClick);
            globalSearchButton.classList.add('here');
        }

        return () => {
            if (globalSearchInput) {
                globalSearchInput.removeEventListener('click', handleInputClick);
            }
            if (globalSearchButton) {
                globalSearchButton.removeEventListener('click', handleButtonClick);
                globalSearchButton.classList.remove('here');
            }
        };
    }, []);

    return (
        <>
            {isModalOpen && <SearchModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            />}
        </>
    );
};

export default GlobalSearch;
